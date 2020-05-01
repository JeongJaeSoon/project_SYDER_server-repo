<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = [
        'order_status', 'sender', 'receiver', 'order_cart', 'order_route', 'request_time', 'approved_time', 'depart_time', 'arrival_time'
    ];
}
