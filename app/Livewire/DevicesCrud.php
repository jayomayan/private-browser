<?php

namespace App\Livewire;

use App\Models\Device;
use App\Models\DeviceLog;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\LazyCollection;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DevicesCrud extends Component
{
    use WithPagination;

    public string $search = '';
    public $showForm = false;
    public $deviceId = null;

    // form fields
    public $ip = '';
    public $site_id = '';
    public $name = '';
    public bool $showDelete = false;
    public array $devicePreview = [];

    protected $rules = [
        'ip'      => ['required','ip'],
        'site_id' => ['nullable','string','max:255'],
        'name'    => ['nullable','string','max:255'],
    ];

    protected $queryString = ['search' => ['except' => '']];

    public function updatingSearch() { $this->resetPage(); }

     public function updated($field)
    {
        if ($field === 'search') {
            $this->resetPage();
        }
    }

    public function render()
    {
         $s = trim($this->search);

        $devices = Device::query()
            ->when($s !== '', function ($q) use ($s) {
                $q->where(function ($qq) use ($s) {
                    $qq->where('ip', 'like', "%{$s}%")
                       ->orWhere('site_id', 'like', "%{$s}%")
                       ->orWhere('name', 'like', "%{$s}%");
                });
            })
            ->orderByDesc('id')
            ->paginate(10);

        return view('livewire.devices-crud', compact('devices'));
    }

    public function create()
    {
        $this->reset(['deviceId','ip','site_id','name']);
        $this->showForm = true;
    }

    public function edit($id)
    {
        $d = Device::findOrFail($id);
        $this->deviceId = $d->id;
        $this->ip = $d->ip;
        $this->site_id = $d->site_id;
        $this->name = $d->name;
        $this->showForm = true;
    }

public function save()
{
    $data = $this->validate();

    // enforce unique IP (your schema has UNIQUE on ip)
    $exists = Device::where('ip', $this->ip)
        ->when($this->deviceId, fn($q) => $q->where('id', '!=', $this->deviceId))
        ->exists();

    if ($exists) {
        $this->addError('ip', 'This IP already exists.');
        return;
    }

    if ($this->deviceId) {
        $device = Device::findOrFail($this->deviceId);
        $device->update($data);
        session()->flash('message', 'Device updated.');
    } else {
        $device = Device::create($data);
        session()->flash('message', 'Device created.');
    }

    // ✅ Extra step: if Enetek, fetch versions with Node script
    if (strtolower($device->name) === 'enetek') {
        try {
            $scriptPath = base_path('node-scripts/download-version-enetek.cjs');
            $command = escapeshellcmd("node $scriptPath {$device->ip} admin admin");

            $output = null;
            $returnVar = null;

            exec($command . ' 2>&1', $output, $returnVar);

            if ($returnVar !== 0) {
                \Log::error("❌ Version fetch failed for device {$device->ip}:\n" . implode("\n", $output));
            } else {
                $json = implode("\n", $output);
                $versions = json_decode($json, true);

                if (json_last_error() !== JSON_ERROR_NONE) {
                    \Log::error("❌ Invalid JSON from version fetch for {$device->ip}:\n$json");
                } elseif (is_array($versions)) {
                    $device->update([
                        'arm_version'    => $versions['arm_version'] ?? null,
                        'stm32_version'  => $versions['stm32_version'] ?? null,
                        'web_version'    => $versions['web_version'] ?? null,
                        'kernel_version' => $versions['kernel_version'] ?? null,
                        'mib_version'    => $versions['mib_version'] ?? null,
                    ]);
                }
            }
        } catch (\Throwable $e) {
            \Log::error("❌ Exception while fetching Enetek versions for {$device->ip}: " . $e->getMessage());
        }
    }

    $this->showForm = false;
    $this->reset(['deviceId', 'ip', 'site_id', 'name']);
}

        public function confirmDelete(int $id): void
        {
            $d = \App\Models\Device::findOrFail($id);

            $this->deviceId = $d->id;
            $this->devicePreview = [
                'id'        => $d->id,
                'ip'        => $d->ip,
                'site_id'   => $d->site_id,
                'name'      => $d->name,
                'created_at'=> optional($d->created_at)->toDayDateTimeString(),
            ];

            $this->showDelete = true;
        }

        public function delete(): void
        {
            if ($this->deviceId) {
                \App\Models\Device::findOrFail($this->deviceId)->delete();
            }

            // reset delete state
            $this->reset(['showDelete', 'deviceId', 'devicePreview']);

            session()->flash('message', 'Device deleted.');
        }


public function exportLogsCsv()
{
    $fileName = 'DeviceLogs.csv';
    $tempPath = storage_path('app/temp_' . uniqid() . '.csv');

    // 1. Open a temp file for writing
    $handle = fopen($tempPath, 'w');

    // 2. Write headers
    fputcsv($handle, [
        'id',
        'ip',
        'site_id',
        'date',
        'time',
        'event',
        'message',
        'created_at',
        'updated_at'
    ]);

    // 3. Write rows in chunks
    \App\Models\DeviceLog::chunk(2000, function ($logs) use ($handle) {
        foreach ($logs as $log) {
            fputcsv($handle, [
                $log->id,
                $log->ip,
                $log->site_id,
                $log->date,
                $log->time,
                $log->event,
                $log->message,
                $log->created_at,
                $log->updated_at,
            ]);
        }
    });

    fclose($handle);

    // 4. Let Laravel handle the download
    return response()->download($tempPath, $fileName, [
        "Content-Type" => "text/csv",
    ])->deleteFileAfterSend(true);
}

        public function exportCsv()
        {
            $fileName = 'Devicesdata.csv';
            $headers = [
                "Content-type"        => "text/csv",
                "Content-Disposition" => "attachment; filename=$fileName",
                "Pragma"              => "no-cache",
                "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
                "Expires"             => "0"
            ];

            // Define your column headers
            $columns = ['id', 'ip', 'site_id', 'name','fw_version','mib_version', 'created_at','last_log_pulled_at'];

            $callback = function() use ($columns) {
                $file = fopen('php://output', 'w');
                fputcsv($file, $columns); // Write headers

                Device::chunk(2000, function ($data) use ($file) {
                    foreach ($data as $row) {
                        // Map your model attributes to CSV row values
                        fputcsv($file, [
                            $row->id,
                            $row->ip,
                            $row->site_id,
                            $row->name,
                            $row->arm_version,
                            $row->mib_version,
                            $row->created_at,
                            $row->last_log_pulled_at,
                        ]);
                    }
                });

                fclose($file);
            };

            return Response::stream($callback, 200, $headers);
        }
}
