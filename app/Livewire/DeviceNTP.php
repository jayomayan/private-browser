<?php

namespace App\Livewire;

use Livewire\Component;

class DeviceNTP extends Component
{
     public $error;


     public function submit()
     {
         $this->error = null;

         try {
            $scriptPath = base_path('node-scripts/setclock.cjs');
                $command = escapeshellcmd("node $scriptPath $this->host $this->device_brand");
                $output = null;
                $returnVar = null;

                exec($command . ' 2>&1', $output, $returnVar);

                if ($returnVar !== 0) {
                    throw new \Exception("NTP configuration script failed:\n" . implode("\n", $output));
                }

             session()->flash('message', 'NTP configuration submitted successfully.');
         } catch (\Exception $e) {
             $this->error = 'Error: ' . $e->getMessage();
         }
     }


    public function render()
    {
        return view('livewire.device-n-t-p');
    }
}
