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
    public $deviceInfo1;
    public $deviceInfo2;

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
        $this->deviceInfo1 = $this->scanIp($this->privateIp, 'type1');
        if (str_contains($this->deviceInfo1, 'no version info found')) {
            $this->deviceInfo1 = $this->scanIp($this->privateIp, 'type2');
        }
        $this->deviceInfo2 = $this->scanIp($this->privateIp, 'type3');

        try {

            putenv('HOME=/tmp');

            Browsershot::url("http://{$this->privateIp}")
            ->setChromePath('/usr/bin/google-chrome') // or your verified path
            ->setOption('args', [
                '--no-sandbox',
                '--disable-dev-shm-usage',
                '--disable-crash-reporter',             // ✅ stops crashpad
                '--user-data-dir=/tmp/chrome-data',     // ✅ avoids /var/www/.local
                '--no-first-run',                       // ✅ suppress setup dialogs
                '--no-default-browser-check'
            ])
            ->windowSize(1280, 720)
            ->timeout(60)
            ->save(storage_path("app/public/snapshots/{$this->privateIp}.png"));

            Browsershot::url("https://{$this->privateIp}")
            ->setChromePath('/usr/bin/google-chrome')
            ->setOption('ignoreHTTPSErrors', true)
            ->setOption('args', [
                '--no-sandbox',
                '--disable-dev-shm-usage',
                '--disable-crash-reporter',
                '--user-data-dir=/tmp/chrome-data',
                '--no-first-run',
                '--no-default-browser-check',
                '--ignore-certificate-errors' // ← this was being skipped before
            ])
            ->windowSize(1280, 720)
            ->timeout(60)
            ->save(storage_path("app/public/snapshots/{$this->privateIp}-s.png"));

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

    public function scanIp(string $ip, string $deviceType): string
    {
        // Set protocol and credentials based on device type
        switch ($deviceType) {
            case 'type3':
                $protocol = 'https';
                $username = 'admin';
                $password = 'admin';
                break;

            case 'type2':
                $protocol = 'http';
                $username = 'admin';
                $password = 'admin@123';
                break;

            case 'type1':
            default:
                $protocol = 'http';
                $username = 'admin';
                $password = 'admin';
                break;
        }

        // Ping check
        $pingCmd = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN'
            ? "ping -n 1 -w 100 $ip"
            : "ping -c 1 -W 1 $ip";
        exec($pingCmd, $output, $status);

        if ($status !== 0) {
            return "[FAIL] $ip - Unreachable";
        }

        // Fetch HTML content

        if ($deviceType === 'type1') {
            $url = "$protocol://$ip/cgi-bin/about_info";
        } else {
            $url = "$protocol://$ip/";
        }
        $context = stream_context_create([
            'http' => [
                'header' => "Authorization: Basic " . base64_encode("$username:$password"),
                'ignore_errors' => true
            ],
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false
            ]
        ]);

        $html = @file_get_contents($url, false, $context);

        if (!$html) {
            return "[ERROR] Unable to fetch HTML from $url.";
        }

        libxml_use_internal_errors(true);
        $dom = new \DOMDocument();
        $dom->loadHTML($html);
        libxml_clear_errors();
        $xpath = new \DOMXPath($dom);

        $info = [];

        // --- Type 1: Table Rows ---
        if ($deviceType === 'type1') {
            foreach ($xpath->query('//tr') as $row) {
                $cols = $row->getElementsByTagName('td');
                if ($cols->length >= 2) {
                    $key = trim($cols->item(0)->nodeValue);
                    $value = trim($cols->item(1)->nodeValue);
                    if (stripos($key, 'version') !== false || stripos($key, 'model') !== false) {
                        $info[$key] = $value;
                    }
                }
            }
        }

        // --- Type 2: <label id="systemVerion"> ---
        if ($deviceType === 'type2') {
            $label = $dom->getElementById('systemVerion');
            if ($label) {
                $info['System Version'] = trim($label->nodeValue);
            }
        }

        // --- Type 3: <label id="qwert_firmware_ver"> ---
        if ($deviceType === 'type3') {
            $label = $dom->getElementById('qwert_firmware_ver');
            if ($label) {
                $info['Firmware Version'] = trim($label->nodeValue);
            }
        }

        if (empty($info)) {
            return "[INFO] $ip - Reachable, but no version info found.";
        }

        $output = "[SUCCESS] $ip - Retrieved info:\n";
        foreach ($info as $key => $value) {
            $output .= "$key: $value\n";
        }

        return $output;
    }

    public function render()
    {
        return view('livewire.private-browser');
    }
}
