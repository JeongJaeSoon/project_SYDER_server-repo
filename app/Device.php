<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Device extends Model
{
    protected $fillable = [
        'device_id',
        'push_service_id'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
