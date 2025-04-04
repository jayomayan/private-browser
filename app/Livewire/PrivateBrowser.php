<?php

namespace App\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Validator;
use Spatie\Browsershot\Browsershot;

class PrivateBrowser extends Component
{
    public $privateIp = '';
    public $isConnected = false;
    public $connectionError = '';
    public $connectionUrl = '';
    public $pingresponse;

    protected $rules = [
        'privateIp' => 'required|ip'
    ];

    public function connect()
    {
        $this->connectionError = '';

        $validator = Validator::make(
            ['privateIp' => $this->privateIp],
            ['privateIp' => 'required|ip']
        );

        if ($validator->fails()) {
            $this->connectionError = 'Please enter a valid IP address.';
            return;
        }

        // Validate if it's a private IP
        if (!$this->isPrivateIp($this->privateIp)) {
            $this->connectionError = 'Please enter a private IP address.';
            return;
        }

        // Format the connection URL (using http by default, could be configurable)
        $this->connectionUrl = "http://{$this->privateIp}";
        $this->isConnected = true;
        $this->pingresponse = $this->pingIP($this->privateIp);

        try {
            Browsershot::url("http://{$this->privateIp}")
                ->setOption('args', ['--no-sandbox'])
                ->windowSize(1280, 720)
                ->save(storage_path("app/public/snapshots/{$this->privateIp}.png"));
        } catch (\Exception $e) {
            logger()->error('Browsershot failed: ' . $e->getMessage());
        }

    }

    public function disconnect()
    {
        $this->isConnected = false;
        $this->connectionUrl = '';
    }

    private function pingIP($ip)
{
    $escapedIp = escapeshellarg($ip);
    $command = "ping -c 5 -W 2 {$escapedIp} 2>&1";

    exec($command, $output, $returnVar);

    $pingResult = [
        'raw_output' => $output, // <-- keep as array
        'success' => $returnVar === 0
    ];

    if (preg_match('/(\d+)% packet loss/', implode("\n", $output), $packetLossMatch)) {
        $pingResult['packet_loss'] = $packetLossMatch[1] . '%';
    }

    if (preg_match('/min\/avg\/max\/mdev = ([\d.]+)\/([\d.]+)\/([\d.]+)\/([\d.]+) ms/', implode("\n", $output), $rttMatch)) {
        $pingResult['rtt'] = [
            'min' => $rttMatch[1] . ' ms',
            'avg' => $rttMatch[2] . ' ms',
            'max' => $rttMatch[3] . ' ms'
        ];
    }

    return $pingResult;
}

    private function isPrivateIp($ip)
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

    public function render()
    {
        return view('livewire.private-browser');
    }
}
