<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = [
        'status', 'sender', 'receiver', 'order_cart', 'order_route', 'reverse_direction', 'request_time', 'approved_time', 'depart_time', 'arrival_time'
    ];
}
