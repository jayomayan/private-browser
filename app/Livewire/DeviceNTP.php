<?php

namespace App\Livewire;

use Livewire\Component;

class DeviceNTP extends Component
{
     public $error;
     public $host;
     public $device_brand;
     public $output;
     public $results;

     public function submit()
{
    $this->reset(['error', 'output', 'results']);

    // 1) Parse IPs: accept commas, spaces, or newlines; trim & dedupe
    $raw = (string) $this->host;
    $ips = array_values(array_unique(array_filter(
        array_map('trim', preg_split('/[,\s]+/', $raw))
    )));

    if (empty($ips)) {
        $this->error = 'Please enter at least one Site IP.';
        return;
    }

    // 2) Validate brand
    if (empty($this->device_brand)) {
        $this->error = 'Please select a device brand.';
        return;
    }

    // 3) Validate each IP (format + private range)
    $invalid = [];
    foreach ($ips as $ip) {
        if (!filter_var($ip, FILTER_VALIDATE_IP) || !isPrivateIp($ip)) {
            $invalid[] = $ip;
        }
    }
    if (!empty($invalid)) {
        $this->error = 'Invalid or non-private IP(s): ' . implode(', ', $invalid);
        return;
    }

    // 4) Run Node script per IP via exec(), capture output + exit code
    $this->results = [];
    $node       = 'node'; // or full path e.g. /usr/bin/node
    $scriptPath = base_path('node-scripts/setclock.cjs');

    foreach ($ips as $ip) {
        try {
            $cmd = sprintf(
                '%s %s %s %s 2>&1',
                escapeshellcmd($node),
                escapeshellarg($scriptPath),
                escapeshellarg($ip),
                escapeshellarg($this->device_brand)
            );

            $lines = [];
            $code  = 0;

            \Log::info("Running setclock.cjs for {$ip} ({$this->device_brand})");
            exec($cmd, $lines, $code);

            $msg = trim(implode("\n", $lines));

            if ($code === 0) {
                $this->results[] = [
                    'ip'      => $ip,
                    'status'  => 'ok',
                    'message' => $msg !== '' ? $msg : 'NTP configuration submitted successfully.',
                ];
            } else {
                $this->results[] = [
                    'ip'      => $ip,
                    'status'  => 'error',
                    'message' => $msg !== '' ? $msg : "Script exited with code {$code}",
                ];
            }
        } catch (\Throwable $e) {
            $this->results[] = [
                'ip'      => $ip,
                'status'  => 'error',
                'message' => 'Execution error: ' . $e->getMessage(),
            ];
        }
    }

    // Optional: set a simple overall message if at least one succeeded
    if (collect($this->results)->contains(fn($r) => $r['status'] === 'ok')) {
        session()->flash('message', 'Submitted. See per-IP results below.');
    }
}


    public function render()
    {
        return view('livewire.device-n-t-p');
    }
}
