<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Route extends Model
{
    protected $fillable = [
        'starting_point', 'arrival_point', 'travel_time', 'travel_distance'
    ];
}
