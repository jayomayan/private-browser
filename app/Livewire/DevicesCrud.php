<?php

namespace App\Livewire;

use App\Models\Device;
use App\Models\DeviceLog;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\LazyCollection;

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
            ->when($this->deviceId, fn($q)=>$q->where('id','!=',$this->deviceId))
            ->exists();
        if ($exists) {
            $this->addError('ip', 'This IP already exists.');
            return;
        }

        if ($this->deviceId) {
            Device::findOrFail($this->deviceId)->update($data);
            session()->flash('message', 'Device updated.');
        } else {
            Device::create($data);
            session()->flash('message', 'Device created.');
        }

        $this->showForm = false;
        $this->reset(['deviceId','ip','site_id','name']);
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
    $headers = [
        "Content-Type"        => "text/csv",
        "Content-Disposition" => "attachment; filename=\"$fileName\"",
        "Cache-Control"       => "no-store, no-cache, must-revalidate",
        "Pragma"              => "no-cache",
    ];

    $columns = ['id', 'ip', 'time', 'message', 'created_at'];

    $callback = function () use ($columns) {
        // disable buffering so rows flush immediately
        if (function_exists('apache_setenv')) {
            @apache_setenv('no-gzip', '1');
        }
        ini_set('zlib.output_compression', '0');
        ini_set('implicit_flush', '1');
        ob_implicit_flush(true);

        $file = fopen('php://output', 'w');
        fputcsv($file, $columns);

        // use cursor() for true streaming (one row at a time)
        foreach (DeviceLog::cursor() as $row) {
            fputcsv($file, [
                $row->id,
                $row->ip,
                $row->time,
                $row->message,
                $row->created_at,
            ]);

            // flush output buffer every 100 rows
            if ($row->id % 100 === 0) {
                ob_flush();
                flush();
            }
        }

        fclose($file);
    };

    return Response::stream($callback, 200, $headers);
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
            $columns = ['id', 'ip', 'site_id', 'name', 'created_at'];

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
