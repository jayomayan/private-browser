<?php
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;


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
