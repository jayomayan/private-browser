<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Device;
use Illuminate\Support\Facades\Log;

class GetDeviceSerialNumber extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'snmp:get-serial {--all : Query all devices (ignore existing serial_number)}';

    /**
     * The console command description.
     */
    protected $description = 'Query all devices via SNMP to retrieve and store their serial numbers';

    /**
     * SNMP OID for serial number.
     */
    private string $serialOid = ".1.3.6.1.4.1.53318.100.2";

    public function handle()
    {
        $devices = $this->option('all')
            ? Device::all()
            : Device::whereNull('serial_number')->orWhere('serial_number', '')->get();

        if ($devices->isEmpty()) {
            $this->info('No devices to process.');
            return self::SUCCESS;
        }

        foreach ($devices as $device) {
            $this->info("🔍 SNMP get serial for {$device->ip} ...");

            $cmd = sprintf(
                'snmpwalk -v2c -c %s %s:%d %s',
                escapeshellarg($device->community ?? 'axinplc'),
                escapeshellarg($device->ip),
                (int) $device->snmp_port ?? 2161,
                escapeshellarg($this->serialOid)
            );

            $output = [];
            $exitCode = 0;
            exec($cmd . ' 2>&1', $output, $exitCode);

            // Log the raw response for debugging
            Log::info("SNMP raw response for {$device->ip}", [
                'exit_code' => $exitCode,
                'response'  => $output,
            ]);

            if ($exitCode !== 0 || empty($output)) {
                Log::error("SNMP query failed for {$device->ip} (exit {$exitCode}) Output: " . implode("\n", $output));
                $this->error("❌ Failed for {$device->ip}");
                continue;
            }

            // Parse SNMP output: OID = STRING: "ABC123"
            $line = $output[0] ?? '';
            $serial = $this->parseSnmpValue($line);

            Log::info("Parsed serial for {$device->ip}", ['serial' => $serial]);

            // Skip invalid SNMP responses
            if (!$serial || str_contains($serial, 'No Such Instance')) {
                $this->warn("⚠️ No valid serial found for {$device->ip} (Response: {$serial})");
                Log::warning("SNMP OID {$this->serialOid} returned invalid response for {$device->ip}: {$serial}");
                continue;
            }

            try {
                $device->update(['serial_number' => $serial]);
                $this->info("✅ {$device->ip} serial: {$serial}");
                Log::info("Serial updated for {$device->ip}", ['serial_number' => $serial]);
            } catch (\Throwable $e) {
                Log::error("❌ Failed to update serial for {$device->ip}: " . $e->getMessage());
                $this->error("❌ DB error for {$device->ip}");
            }
        }

        $this->info('🎉 SNMP serial collection complete.');
        return self::SUCCESS;
    }

    /**
     * Parse SNMP output into a clean string value.
     */
   private function parseSnmpValue(string $line): ?string
{
    if (!str_contains($line, '=')) {
        return null;
    }

    [, $rhs] = explode('=', $line, 2);
    $rhs = trim($rhs);

    // Handle STRING: "value"
    if (stripos($rhs, 'STRING:') === 0) {
        return trim(str_replace(['STRING:', '"'], '', $rhs));
    }

    // Handle Hex-STRING: 41 42 43 -> ABC
    if (stripos($rhs, 'Hex-STRING:') === 0) {
        $hex = preg_replace('/\s+/', '', substr($rhs, strlen('Hex-STRING:')));
        return @hex2bin($hex) ?: null;
    }

    // Fallback: return trimmed RHS
    return trim($rhs, '"');
}
}
