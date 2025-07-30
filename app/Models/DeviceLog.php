<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeviceLog extends Model
{
    protected $fillable = ['ip', 'date', 'time', 'message'];

    // Optional: reverse relationship
    public function device()
    {
        return $this->belongsTo(Device::class, 'ip', 'ip');
    }
}
