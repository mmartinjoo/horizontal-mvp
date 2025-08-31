<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class DocumentTopic extends Model
{
    protected $guarded = [];

    protected $casts = [
        'variations' => 'array',
    ];

    public function entity(): MorphTo
    {
        return $this->morphTo();
    }
}
