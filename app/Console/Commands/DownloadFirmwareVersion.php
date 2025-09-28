<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Device;
use Carbon\Carbon;

class DownloadFirmwareVersion extends Command
{
    // The command name (how you call it)
    protected $signature = 'firmware:download';

    // Description for "php artisan list"
    protected $description = 'Download firmware versions from all registered devices';

    public function handle(): void
    {
       // $devices = Device::all();
        $devices = Device::whereNull('arm_version')
            ->orWhere('arm_version', '')
            ->get();

        foreach ($devices as $device) {
            if (strtolower($device->name) === 'enetek') {
                try {
                    $this->info("🔍 Fetching firmware for {$device->ip} ...");

                    $scriptPath = base_path('node-scripts/download-version-enetek.cjs');
                    $command = escapeshellcmd("node $scriptPath {$device->ip} admin admin");

                    $output = null;
                    $returnVar = null;

                    exec($command . ' 2>&1', $output, $returnVar);

                    if ($returnVar !== 0) {
                        \Log::error("❌ Version fetch failed for device {$device->ip}:\n" . implode("\n", $output));
                        $this->error("❌ Failed: {$device->ip}");
                        continue;
                    }

                    $json = implode("\n", $output);
                    $versions = json_decode($json, true);

                    if (json_last_error() !== JSON_ERROR_NONE) {
                        \Log::error("❌ Invalid JSON from version fetch for {$device->ip}:\n$json");
                        $this->error("❌ Invalid JSON: {$device->ip}");
                        continue;
                    }

                    if (is_array($versions)) {
                        $device->update([
                            'arm_version'    => $versions['arm_version'] ?? null,
                            'stm32_version'  => $versions['stm32_version'] ?? null,
                            'web_version'    => $versions['web_version'] ?? null,
                            'kernel_version' => $versions['kernel_version'] ?? null,
                            'mib_version'    => $versions['mib_version'] ?? null,
                            'last_firmware_pulled_at' => Carbon::now(),
                        ]);
                        $this->info("✅ Processed: {$device->ip}");
                    }
                } catch (\Throwable $e) {
                    \Log::error("❌ Exception while fetching Enetek versions for {$device->ip}: " . $e->getMessage());
                    $this->error("❌ Exception: {$device->ip}");
                }
            } else {
                $this->line("⏭ Skipped non-Enetek device: {$device->ip}");
            }
        }
    }
}
