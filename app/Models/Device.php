<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Device extends Model
{
        protected $fillable = ['ip', 'name', 'site_id']; // allow mass assignment

    // Optional: define relationship if you want
    public function logs()
    {
        return $this->hasMany(DeviceLog::class, 'ip', 'ip');
    }
}
