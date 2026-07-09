<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Group extends Model
{
    protected $fillable = [
        'key', 'name', 'gradient', 'theme', 'scroll_light', 'scroll_dark', 'briques_actives', 'superset_url', 'dolibarr_url'
    ];

    protected $casts = [
        'briques_actives' => 'array',
    ];
}