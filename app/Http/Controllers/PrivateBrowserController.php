<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Validator;



class PrivateBrowserController extends Controller
{
    /**
     * Show the private browser input form
     */
    public function showBrowserForm()
    {
        return view('private-browser.input');
    }

    /**
     * Validate and process the private IP connection
     */
    public function connectPrivateIP(Request $request)
    {
        // Validate input
        $validator = Validator::make($request->all(), [
            'private_ip' => [
                'required',
                'ip',
                // Custom validation to prevent public IP access
                function ($attribute, $value, $fail) {
                    // Check for private IP ranges
                    $privateRanges = [
                        '10.0.0.0/8',
                        '172.16.0.0/12',
                        '192.168.0.0/16',
                        '127.0.0.0/8'
                    ];

                    $isPrivate = false;
                    foreach ($privateRanges as $range) {
                        if ($this->ipInRange($value, $range)) {
                            $isPrivate = true;
                            break;
                        }
                    }

                    if (!$isPrivate) {
                        $fail('The IP must be a private network address.');
                    }
                },
            ]
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $privateIp = $request->input('private_ip');

        try {
            // Log the access attempt
            Log::info('Private IP Access Attempt', [
                'user_id' => Auth::id(),
                'ip' => $privateIp,
                'timestamp' => now()
            ]);

            // Ping the IP address
            $pingResult = $this->pingIP($privateIp);

            // Fetch content from private IP
            $client = new Client([
                'timeout' => 10,
                'verify' => false
            ]);

            $response = $client->get("http://{$privateIp}");
            $content = $response->getBody()->getContents();

            return view('private-browser.results', [
                'content' => $content,
                'privateIp' => $privateIp,
                'pingResult' => $pingResult
            ]);

        } catch (\Exception $e) {
            Log::error('Private IP Access Error', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
                'ip' => $privateIp
            ]);

            return redirect()->back()
                ->with('error', 'Unable to connect to the specified private IP.');
        }
    }

    /**
     * Ping an IP address
     */
    private function pingIP($ip)
    {
        // Sanitize input to prevent command injection
        $escapedIp = escapeshellarg($ip);

        // Ping parameters
        $command = "ping -c 4 -W 2 {$escapedIp} 2>&1";

        // Execute ping command
        exec($command, $output, $returnVar);

        // Process ping results
        $pingResult = [
            'raw_output' => implode("\n", $output),
            'success' => $returnVar === 0
        ];

        // Extract statistics if available
        if (preg_match('/(\d+)% packet loss/', $pingResult['raw_output'], $packetLossMatch)) {
            $pingResult['packet_loss'] = $packetLossMatch[1] . '%';
        }

        if (preg_match('/min\/avg\/max\/mdev = ([\d.]+)\/([\d.]+)\/([\d.]+)\/([\d.]+) ms/', $pingResult['raw_output'], $rttMatch)) {
            $pingResult['rtt'] = [
                'min' => $rttMatch[1] . ' ms',
                'avg' => $rttMatch[2] . ' ms',
                'max' => $rttMatch[3] . ' ms'
            ];
        }

        return $pingResult;
    }

    /**
     * Check if an IP is within a given range
     */
    private function ipInRange($ip, $range)
    {
        list($range, $netmask) = explode('/', $range);
        $range_decimal = ip2long($range);
        $ip_decimal = ip2long($ip);
        $wildcard_decimal = pow(2, (32 - $netmask)) - 1;
        $netmask_decimal = ~ $wildcard_decimal;
        return (($ip_decimal & $netmask_decimal) == ($range_decimal & $netmask_decimal));
    }
}
