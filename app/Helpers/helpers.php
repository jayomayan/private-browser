<?php

namespace App\Helpers;

class LogDownloader
{
    public static function downloadLogs($ip, $username = 'admin', $password = 'admin')
    {
        $scriptPath = base_path('node-scripts/download-logs.js'); // Adjust path if needed
        $command = escapeshellcmd("node $scriptPath $ip $username $password");

        $output = null;
        $returnVar = null;

        exec($command . ' 2>&1', $output, $returnVar);

        if ($returnVar !== 0) {
            throw new \Exception("Download script failed:\n" . implode("\n", $output));
        }

        return implode("\n", $output);
    }
}
