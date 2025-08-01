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
        $this->logData = $logData;
    }

    public function handle()
    {

        $bigQuery = new BigQueryClient([
            'projectId' => env('GOOGLE_CLOUD_PROJECT_ID','ftap-cmmp-dw-prod'),
            'keyFilePath' => env('GOOGLE_APPLICATION_CREDENTIALS','/var/www/secure_keys/sa-private-key.json'),
        ]);

        $dataset = $bigQuery->dataset(env('BQ_DATASET','ftap-cmmp-dw-prod'));
        $table = $dataset->table(env('BQ_TABLE','device_logs'));

        $insertResponse = $table->insertRows([
            [
                'data' => [
                    'ip'      => $this->logData['ip'],
                    'site_id' => $this->logData['site_id'],
                    'date'    => $this->logData['date'],
                    'time'    => $this->logData['time'],
                    'event'   => $this->logData['event'],
                    'message' => $this->logData['message'],
                ],
            ],
        ]);

        if ($insertResponse->isSuccessful()) {
          #  \Log::info('Log pushed to BigQuery: ' . $this->logData['site_id']);
        } else {
           # \Log::error('BigQuery insert failed: ', $insertResponse->failedRows());
        }
    }
}
