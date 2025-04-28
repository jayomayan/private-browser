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
            // Normalize the OIDs input: replace spaces with commas first
            $normalizedOidInput = str_replace(' ', ',', $this->oid);

            // Then split by comma (so both commas and spaces are handled)
            $oids = array_filter(array_map('trim', explode(',', $normalizedOidInput)));

            foreach ($oids as $singleOid) {
                $cmd = sprintf(
                    'snmpwalk -v2c -c %s %s:%d %s',
                    escapeshellarg($this->community),
                    escapeshellarg($this->host),
                    (int) $this->port,
                    escapeshellarg($singleOid)
                );

                exec($cmd, $output, $exitCode);

                if ($exitCode !== 0) {
                    $this->snmpError = "SNMP command failed for OID {$singleOid} (code: $exitCode)";
                    continue;
                }

                foreach ($output as $line) {
                    if (strpos($line, ' = ') !== false) {
                        [$oidResult, $value] = explode(' = ', $line, 2);
                        $this->results[] = [
                            'oid' => trim($oidResult),
                            'value' => trim($value),
                        ];
                    }
                }

                $output = []; // Clear for next loop
            }

            if (empty($this->results)) {
                $this->snmpError = 'SNMP returned no results for any provided OIDs.';
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
