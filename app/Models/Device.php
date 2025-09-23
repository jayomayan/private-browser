<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Device extends Model
{
   protected $fillable = [
        'ip',
        'name',
        'site_id',
        'arm_version',
        'stm32_version',
        'web_version',
        'kernel_version',
        'mib_version',
    ];
    // allow mass assignment
  protected $casts = ['last_log_pulled_at' => 'datetime',];

    // Optional: define relationship if you want
    public function logs()
    {
        return $this->hasMany(DeviceLog::class, 'ip', 'ip');
    }
}
