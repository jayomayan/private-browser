<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use App\Helpers\helpers;

class LogDownloadController extends Controller
{
    /**
     * POST /api/download-logs
     * Requires a Sanctum bearer token.
     */
    public function download(Request $request)
    {
        // Optional ability check if you limited the token
        // if (! $request->user()->tokenCan('download-logs')) {
        //     return response()->json(['error' => 'Token lacks ability'], 403);
        // }

        $data = $request->validate([
            'ip'       => ['required', 'ip'],
            'username' => ['nullable', 'string'],
            'password' => ['nullable', 'string'],
        ]);

        try {
            $output = helpers::downloadLogs(
                $data['ip'],
                $data['username'] ?? 'admin',
                $data['password'] ?? 'admin'
            );

            return response()->json([
                'message' => 'CSV downloaded successfully.',
                'output'  => $output   // stdout from Node script
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'error'   => 'Download failed',
                'detail'  => $e->getMessage()
            ], 500);
        }
    }
}
