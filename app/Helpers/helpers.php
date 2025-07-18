<?php


use Illuminate\Support\Facades\Log;

if (!function_exists('download_logs')) {
function download_logs($ip, $username = 'admin', $password = 'admin')
{
    $scriptPath = base_path('node-scripts/download-logs.cjs');

    $command = "node $scriptPath $ip $username $password";

    Log::info($scriptPath);

    // 🔍 Add this line to log what Laravel is actually running:
    file_put_contents(storage_path('logs/download-command.log'), $command . PHP_EOL, FILE_APPEND);

    $output = null;
    $returnVar = null;

    exec(escapeshellcmd($command) . ' 2>&1', $output, $returnVar);

    if ($returnVar !== 0) {
        throw new \Exception("Download script failed:\n" . implode("\n", $output));
    }

    return implode("\n", $output);
}
}
