<?php

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

        return implode("\n", $output);
    }
}
