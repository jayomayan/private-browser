<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Device;
use Carbon\Carbon;

class DownloadDeviceLogs extends Command
{
    protected $signature = 'logs:download';
    protected $description = 'Download logs from all registered devices';

    public function handle(): void
    {
        $devices = Device::all();

        foreach ($devices as $device) {
            try {
                processLogs($device->ip);
                $device->last_log_pulled_at = Carbon::now();
                $device->save();
                $this->info("✅ Processed: {$device->ip}");
            } catch (\Throwable $e) {
                \Log::error("❌ Error processing {$device->ip}: " . $e->getMessage());
                $this->error("❌ Failed: {$device->ip}");
            }
        }
    }
}
