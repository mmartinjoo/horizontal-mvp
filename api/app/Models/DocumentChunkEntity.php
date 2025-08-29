<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentChunkEntity extends Model
{
    protected $guarded = [];

    protected $casts = [
        'keywords' => 'array',
        'people' => 'array',
        'dates' => 'array',
    ];

    public function chunk()
    {
        return $this->belongsTo(DocumentChunk::class);
    }
}
