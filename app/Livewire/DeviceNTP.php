<?php

namespace App\Livewire;

use Livewire\Component;

class DeviceNTP extends Component
{
     public $error;
     public $host;
     public $device_brand;
     public $output;
     public $results;

     public function submit()
     {
         $this->reset(['error', 'output', 'results']);

        // Basic validation (adjust as you like)
        if (!filter_var($this->host, FILTER_VALIDATE_IP) && !preg_match('/^[a-z0-9\.\-]+$/i', $this->host)) {
            $this->error = 'Invalid host (must be IP or hostname).';
            return;
        }
        if (empty($this->device_brand)) {
            $this->error = 'Please select a device brand.';
            return;
        }

         try {
                $scriptPath = base_path('node-scripts/setclock.cjs');
                $command = escapeshellcmd("node $scriptPath $this->host $this->device_brand");
                $output = null;
                $returnVar = null;

                exec($command . ' 2>&1', $output, $returnVar);

                if ($returnVar !== 0) {
                    throw new \Exception("NTP configuration script failed:\n" . implode("\n", $output));
                }

             $this->results = "NTP configuration submitted successfully.";
         } catch (\Exception $e) {
             $this->error = 'Error: ' . $e->getMessage();
         }
     }


    public function render()
    {
        return view('livewire.device-n-t-p');
    }
}
