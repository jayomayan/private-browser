<?php

namespace App\Jobs;

use Google\Cloud\BigQuery\BigQueryClient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class PushToBigQueryJob implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    protected array $logData;

    public function __construct(array $logData)
    {
        $this->logData = $logData;
    }

    public function handle()
    {
        Log::info('PushToBigQueryJob executed for IP: ' . $this->logData['ip']);

        $bigQuery = new BigQueryClient([
            'projectId'   => env('GOOGLE_CLOUD_PROJECT_ID'),
            'keyFilePath' => env('GOOGLE_APPLICATION_CREDENTIALS'),
        ]);

        $dataset = $bigQuery->dataset(env('BQ_DATASET'));
        $table = $dataset->table(env('BQ_TABLE'));

        $insertResponse = $table->insertRows([
            [
                'data' => [
                    'ip'      => $this->logData['ip'],
                    'site_id' => $this->logData['site_id'],
                    'date'    => $this->logData['date'],
                    'time'    => $this->logData['time'],
                    'message' => $this->logData['message'],
                ]
            ]
        ]);

        if ($insertResponse->isSuccessful()) {
            Log::info('✅ Log pushed to BigQuery: ' . json_encode($this->logData));
        } else {
            Log::error('❌ BigQuery insert failed', $insertResponse->failedRows());
        }
    }
}
