<?php
namespace App\Jobs;

use App\Models\DeviceLog;
use Google\Cloud\BigQuery\BigQueryClient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class PushToBigQueryJob implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    protected $logData;

    public function __construct(array $logData)
    {
        $this->log = $logData;
    }

    public function handle()
    {

        Log::info('PushToBigQueryJob executed for log ID: ' . $this->log->id);

        $bigQuery = new BigQueryClient([
            'projectId' => env('GOOGLE_CLOUD_PROJECT_ID'),
            'keyFilePath' => env('GOOGLE_APPLICATION_CREDENTIALS'),
        ]);

        $dataset = $bigQuery->dataset(env('BQ_DATASET'));
        $table = $dataset->table(env('BQ_TABLE'));

        $insertResponse = $table->insertRows([
            [
                'ip'      => $this->log->ip,
                'site_id' => $this->log->site_id,
                'date'    => $this->log->date,
                'time'    => $this->log->time,
                'message' => $this->log->message,
            ],
        ]);

        if ($insertResponse->isSuccessful()) {
            \Log::info('Log pushed to BigQuery: ' . $this->log->id);
        } else {
            \Log::error('BigQuery insert failed: ', $insertResponse->failedRows());
        }
    }
}
