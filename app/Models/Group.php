<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Group extends Model
{
    protected $fillable = [
        'key', 'name', 'gradient', 'theme', 'scroll_light', 'scroll_dark', 'briques_actives'
    ];

    protected $casts = [
        'briques_actives' => 'array',
    ];
}