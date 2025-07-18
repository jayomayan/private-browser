<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class LogDownloadController extends Controller
{
 public function download(Request $request)
{
    $data = $request->validate([
        'ip' => ['required', 'ip'],
        'username' => ['nullable', 'string'],
        'password' => ['nullable', 'string'],
    ]);

    try {
        $csvContent = download_logs(
            $data['ip'],
            $data['username'] ?? 'admin',
            $data['password'] ?? 'admin'
        );

        return response($csvContent)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', 'attachment; filename="exported.csv"');

    } catch (\Throwable $e) {
        return response()->json([
            'error' => 'Download failed',
            'detail' => $e->getMessage()
        ], 500);
    }
}
}
