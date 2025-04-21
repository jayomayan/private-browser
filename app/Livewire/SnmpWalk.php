<?php

namespace App\Livewire;

use Livewire\Component;
use FreeDSx\Snmp\SnmpClient;
use FreeDSx\Snmp\Oid;
use Illuminate\Support\Facades\Log;

class SnmpWalk extends Component
{
    public $host;
    public $port = 2161;
    public $community = 'axinplc';
    public $oid = '.1.3.6.1.4.1.53318.100.1.0'; // Default to sysDescr
    public $results = [];
    public $error;

    public function submit()
    {
        $this->snmpError = null;
        $this->results = [];

        try {
            $cmd = sprintf(
                'snmpwalk -v2c -c %s %s:%d %s',
                escapeshellarg($this->community),
                escapeshellarg($this->host),
                (int) $this->port,
                escapeshellarg($this->oid)
            );

            exec($cmd, $output, $exitCode);

            if ($exitCode !== 0) {
                $this->snmpError = "SNMP command failed to execute (code: $exitCode)";
                return;
            }

            foreach ($output as $line) {
                if (strpos($line, ' = ') !== false) {
                    [$oid, $value] = explode(' = ', $line, 2);
                    $this->results[] = [
                        'oid' => trim($oid),
                        'value' => trim($value),
                    ];
                }
            }

            if (empty($this->results)) {
                $this->snmpError = 'SNMP returned no results.';
            }
        } catch (\Exception $e) {
            $this->snmpError = $e->getMessage();
        }
    }

    public function render()
    {
        return view('livewire.snmp-walk');
    }
}
