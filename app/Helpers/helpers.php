<?php
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use App\Models\DeviceLog;
use Carbon\Carbon;


if (!function_exists('isPrivateIp')) {
    function isPrivateIp($ip)
    {
        // Check if IP is in private ranges
        $privateRanges = [
            '10.0.0.0|10.255.255.255',     // 10.0.0.0/8
            '172.16.0.0|172.31.255.255',   // 172.16.0.0/12
            '192.168.0.0|192.168.255.255', // 192.168.0.0/16
            '127.0.0.0|127.255.255.255'    // localhost
        ];

        $ip = ip2long($ip);

        foreach ($privateRanges as $range) {
            list($start, $end) = explode('|', $range);
            if ((ip2long($start) <= $ip) && ($ip <= ip2long($end))) {
                return true;
            }
        }

        return false;
    }
}

// Function to process logs from a device
if (!function_exists('processLogs')) {
function processLogs($ip)
    {
    $device = \App\Models\Device::where('ip', $ip)->first();
    Log::info("Processing logs for device: {$device->site_id}");

    if (!$device) {
        \Log::warning("Device with IP {$ip} not found.");
        return;
    }

    if ($device->name=="Enetek") {
        // Specific processing for Enetek devices
      try {
            $logString = download_logs($ip);
            $lines = explode("\n", trim($logString));
            // Skip header row
            $lines = array_slice($lines, 5);

            foreach ($lines as $line) {
                $parts = str_getcsv($line);
                // \Log::info("Parts: " . json_encode($parts) . " for device {$ip}");

                if (count($parts) < 4) continue;
                [$no, $datetime, $event, $alarmName] = array_map('trim', $parts);
                try {
                    $timestamp = \Carbon\Carbon::parse($datetime);
                } catch (\Exception $e) {
                    \Log::warning("Invalid datetime: {$datetime} for device {$ip}");
                    continue;
                }

                $logData = [
                'ip'      => $device->ip,
                'site_id' => $device->site_id,
                'date'    => $timestamp->toDateString(),
                'time'    => $timestamp->toTimeString(),
                'event'   => $event,
                'message' => $alarmName,
                ];

            # Log::info("Processing log entry: {$logData['ip']}, {$logData['date']}, {$logData['event']}, {$logData['message']}");

                // Prevent duplicates
                $exists = \App\Models\DeviceLog::where([
                    'ip'      => $logData['ip'],
                    'date'    => $logData['date'],
                    'time'    => $logData['time'],
                    'event'   => $logData['event'],
                    'message' => $logData['message'],
                ])->exists();

                if (!$exists) {
                    \App\Models\DeviceLog::create($logData);
                    // Push to BigQuery
                    dispatch(new \App\Jobs\PushToBigQueryJob($logData));
                }
            }

            } catch (\Exception $e) {
                \Log::error("Error processing logs from {$ip}: " . $e->getMessage());
            }

    } elseif ($device->name=="Vnt") {
       // Specific processing for Vnt devices
          // Specific processing for Enetek devices
      try {
            $logString = download_logs2($ip);
            $lines = explode("\n", trim($logString));
            // Skip header row
            $lines = array_slice($lines, 5);

            foreach ($lines as $line) {
                $parts = str_getcsv($line);
                // \Log::info("Parts: " . json_encode($parts) . " for device {$ip}");

                if (count($parts) < 4) continue;
                [$no, $datetime, $event, $alarmName] = array_map('trim', $parts);
                try {
                    $timestamp = \Carbon\Carbon::parse($datetime);
                } catch (\Exception $e) {
                    \Log::warning("Invalid datetime: {$datetime} for device {$ip}");
                    continue;
                }

                $logData = [
                'ip'      => $device->ip,
                'site_id' => $device->site_id,
                'date'    => $timestamp->toDateString(),
                'time'    => $timestamp->toTimeString(),
                'event'   => $event,
                'message' => $alarmName,
                ];

            # Log::info("Processing log entry: {$logData['ip']}, {$logData['date']}, {$logData['event']}, {$logData['message']}");

                // Prevent duplicates
                $exists = \App\Models\DeviceLog::where([
                    'ip'      => $logData['ip'],
                    'date'    => $logData['date'],
                    'time'    => $logData['time'],
                    'event'   => $logData['event'],
                    'message' => $logData['message'],
                ])->exists();

                if (!$exists) {
                    \App\Models\DeviceLog::create($logData);
                    // Push to BigQuery
                    dispatch(new \App\Jobs\PushToBigQueryJob($logData));
                }
            }
            } catch (\Exception $e) {
                \Log::error("Error processing logs from {$ip}: " . $e->getMessage());
            }

    } elseif ($device->name=="Eltek") {
        // Specific processing for Eltek devices

    } elseif ($device->name=="Huawei") {
        // Specific processing for Huawei devices

    }

    }
}

// for VNT devices
if (!function_exists('download_logs2')) {
function download_logs2($ip, $username = 'admin', $password = 'admin@123')
{
    $scriptPath = base_path('node-scripts/download-logs-vnt.cjs');
    $command = escapeshellcmd("node $scriptPath $ip $username $password");

    $output = null;
    $returnVar = null;

    exec($command . ' 2>&1', $output, $returnVar);

    if ($returnVar !== 0) {
        throw new \Exception("Download script failed:\n" . implode("\n", $output));
    }

    return implode("\n", $output); // now this is raw CSV content
}
}

// Function to download logs from a remote server using Node.js script
if (!function_exists('download_logs')) {
function download_logs($ip, $username = 'admin', $password = 'admin')
{
    $scriptPath = base_path('node-scripts/download-logs.cjs');
    $command = escapeshellcmd("node $scriptPath $ip $username $password");

    $output = null;
    $returnVar = null;

    exec($command . ' 2>&1', $output, $returnVar);

    if ($returnVar !== 0) {
        throw new \Exception("Download script failed:\n" . implode("\n", $output));
    }

    return implode("\n", $output); // now this is raw CSV content
}
}

// Function to get Aeris token

if (!function_exists('getAerisToken')) {
    function getAerisToken(): ?string
    {
        $url = 'https://pccwg.iot.aeris.com/iot/api/auth/token';

        $response = Http::asForm()
            ->withHeaders([
                'Accept' => 'application/vnd.dcp-v1+json',
                'Content-Type' => 'application/x-www-form-urlencoded',
            ])
            ->post($url, [
                'grant_type' => 'password',
                'client_id' => 'cm-public-api-client',
                'username' => 'ftap-jay',
                'password' => 'pRo5&Iq9KEs', // Laravel will URL-encode this automatically
            ]);

        if ($response->successful()) {
            return $response->json()['access_token'];
        }

        \Log::error('Aeris token request failed', [
            'status' => $response->status(),
            'body' => $response->body(),
        ]);

        return null;
    }
}

// Function to search Aeris by label

if (!function_exists('searchAerisByLabel')) {
    function searchAerisByLabel(string $label, string $accessToken): ?array
    {
        $url = "https://iot-api.aeris.com/iot/api/subscriptions/details?q=label=={$label}";

        $response = Http::withHeaders([
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $accessToken,
        ])->get($url);

        if ($response->successful()) {
            return $response->json();
        }

        \Log::error('Aeris label search failed', [
            'status' => $response->status(),
            'body' => $response->body(),
        ]);

        return null;
    }
}

// Function to get Aeris events by IMSI

if (!function_exists('getAerisEventsByImsi')) {
    function getAerisEventsByImsi(string $imsi, string $accessToken): ?array
    {
        $url = "https://iot-api.aeris.com/iot/api/xdr/events?imsi={$imsi}";

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $accessToken,
        ])->get($url);

        if ($response->successful()) {
            return $response->json();
        }

        \Log::error('Failed to fetch Aeris events by IMSI', [
            'imsi' => $imsi,
            'status' => $response->status(),
            'body' => $response->body(),
        ]);

        return null;
    }
}

//Get the most recent Aeris event by IMSI
if (!function_exists('getLatestAerisEventId')) {
    function getLatestAerisEventId(array $eventsResponse): ?string
    {
        if (!isset($eventsResponse['items']) || empty($eventsResponse['items'])) {
            return null;
        }

        // Sort items by occurred_at descending
        $sorted = collect($eventsResponse['items'])->sortByDesc('occurred_at')->values();

        return $sorted->first()['id'] ?? null;
    }
}

// Function to get Aeris event details by event ID

if (!function_exists('getAerisEventDetails')) {
    function getAerisEventDetails(string $eventId, string $accessToken): ?array
    {
        $url = "https://iot-api.aeris.com/iot/api/xdr/events/{$eventId}";

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $accessToken,
        ])->get($url);

        if ($response->successful()) {
            return $response->json();
        }

        \Log::error('Failed to fetch Aeris event details', [
            'event_id' => $eventId,
            'status' => $response->status(),
            'body' => $response->body(),
        ]);

        return null;
    }
}
