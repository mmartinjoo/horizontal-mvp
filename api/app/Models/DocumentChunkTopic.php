<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentChunkTopic extends Model
{
    protected $guarded = [];

    protected $casts = [
        'variations' => 'array',
    ];
}
