<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class LogDownloadController extends Controller
{
    public function download(Request $request)
    {
        $data = $request->validate([
            'ip'       => ['required', 'ip'],
            'username' => ['nullable', 'string'],
            'password' => ['nullable', 'string'],
        ]);

        try {
            $output = download_logs(
                $data['ip'],
                $data['username'] ?? 'admin',
                $data['password'] ?? 'admin'
            );

            return response()->json([
                'message' => 'Download successful.',
                'output'  => $output
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'error'  => 'Download failed',
                'detail' => $e->getMessage()
            ], 500);
        }
    }
}
