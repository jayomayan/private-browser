<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Device;
use Illuminate\Support\Facades\Log;

class GetDeviceSerialNumber extends Command
{


    protected $signature = 'snmp:get-serial';
    protected $description = 'Query all devices via SNMP to retrieve and store their serial numbers';

    // OID provided by you for serial number
    private string $serialOid = '.1.3.6.1.4.1.53318.100.2';

    public function handle()
    {
        // Decide which devices to process
        $devices = $this->option('all')
            ? Device::query()->get()
            : Device::query()->whereNull('serial_number')->orWhere('serial_number', '')->get();

        if ($devices->isEmpty()) {
            $this->info('No devices to process.');
            return self::SUCCESS;
        }

        $defaults = config('devices.snmp', []);
        $defCommunity = $defaults['community'] ?? 'axinplc';
        $defPort      = $defaults['port'] ?? 2161;
        $timeout      = $defaults['timeout'] ?? 2;
        $retries      = $defaults['retries'] ?? 1;

        foreach ($devices as $device) {
            $ip        = $device->ip;
            $community = $device->community ?: $defCommunity;
            $port      = $device->snmp_port ?: $defPort;

            $this->info("🔍 SNMP get serial for {$ip} ...");

            // Build snmpget command (v2c)
            // -t timeout, -r retries
            $cmd = sprintf(
                'snmpget -v2c -t %d -r %d -c %s %s:%d %s',
                (int) $timeout,
                (int) $retries,
                escapeshellarg($community),
                escapeshellarg($ip),
                (int) $port,
                escapeshellarg($this->serialOid)
            );

            $output = [];
            $exit   = 0;
            exec($cmd . ' 2>&1', $output, $exit);

            if ($exit !== 0 || empty($output)) {
                Log::error("SNMP serial query failed for {$ip} (exit: {$exit}). Output:\n" . implode("\n", $output));
                $this->error("❌ Failed for {$ip}");
                continue;
            }

            // Parse first line like:
            // .1.3... = STRING: "ABC123"   OR   = Hex-STRING: 41 42 43 ...
            $line = $output[0] ?? '';
            $serial = $this->parseSnmpValue($line);

            if (!$serial) {
                Log::warning("No serial parsed for {$ip}. Raw:\n" . implode("\n", $output));
                $this->warn("⚠️ No serial for {$ip}");
                continue;
            }

            $device->serial_number = $serial;
            $device->save();

            Log::info("Serial updated for {$ip}: {$serial}");
            $this->info("✅ {$ip}: {$serial}");
        }

        $this->info('🎉 Done.');
        return self::SUCCESS;
    }

    /**
     * Parse SNMP output value portion into a string serial.
     */
    private function parseSnmpValue(string $line): ?string
    {
        // Expected "OID = TYPE: VALUE"
        if (!str_contains($line, '=')) {
            return null;
        }

        [, $rhs] = explode('=', $line, 2);
        $rhs = trim($rhs);

        // Handle STRING: "value"
        if (stripos($rhs, 'STRING:') === 0) {
            $v = trim(substr($rhs, strlen('STRING:')));
            return trim($v, " \t\n\r\0\x0B\"");
        }

        // Handle Hex-STRING: 41 42 43 -> ABC
        if (stripos($rhs, 'Hex-STRING:') === 0) {
            $hex = trim(substr($rhs, strlen('Hex-STRING:')));
            $hex = preg_replace('/\s+/', '', $hex);
            if ($hex && ctype_xdigit($hex)) {
                return @hex2bin($hex) ?: null;
            }
        }

        // Fallback: just return RHS without quotes/type if present
        return trim($rhs, " \t\n\r\0\x0B\"");
    }
}
