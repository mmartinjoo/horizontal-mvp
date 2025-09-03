<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Topic extends Model
{
    protected $guarded = [];

    protected $casts = [
        'variations' => 'array',
        'embedding' => 'array',
    ];
}
