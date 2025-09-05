<?php

namespace App\Http\Controllers;

use App\Models\DeviceLog;
use Illuminate\Http\Request;

class DeviceLogExportController extends Controller
{
    public function export()
    {
        $fileName = 'DeviceLogs.csv';
        $headers = [
            "Content-Type"        => "text/csv; charset=UTF-8",
            "Content-Disposition" => "attachment; filename=\"{$fileName}\"",
            "Cache-Control"       => "no-store, no-cache, must-revalidate",
            "Pragma"              => "no-cache",
        ];

        return response()->streamDownload(function () {
            // keep long jobs alive and avoid buffering
            @ini_set('zlib.output_compression', '0');
            @ini_set('output_buffering', 'off');
            @ini_set('implicit_flush', '1');
            while (ob_get_level() > 0) { @ob_end_flush(); }
            ob_implicit_flush(true);
            set_time_limit(0);

            $out = fopen('php://output', 'w');


                'ip'      => $device->ip,
                'site_id' => $device->site_id,
                'date'    => $timestamp->toDateString(),
                'time'    => $timestamp->toTimeString(),
                'event'   => $event,
                'message' => $alarmName,

            // CSV header row
            fputcsv($out, ['id', 'ip', 'site_id', 'date', 'time', 'event', 'message', 'created_at']);

            $i = 0;
            foreach (DeviceLog::cursor() as $row) {
                fputcsv($out, [
                    $row->id,
                    $row->ip,
                    $row->site_id,
                    $row->date,
                    $row->time,
                    $row->event,
                    $row->message,
                    $row->created_at,
                ]);

                // push bytes every 500 rows
                if ((++$i % 500) === 0) {
                    @ob_flush();
                    @flush();
                }
            }

            fclose($out);
        }, $fileName, $headers);
    }
}
