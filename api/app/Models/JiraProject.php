<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JiraProject extends Model
{
    protected $guarded = [];

    protected $casts = [
        'embedding' => 'array',
    ];
}
