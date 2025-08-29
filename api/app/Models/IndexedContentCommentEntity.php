<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IndexedContentCommentEntity extends Model
{
    protected $guarded = [];

    protected $casts = [
        'keywords' => 'array',
        'people' => 'array',
        'dates' => 'array',
    ];

    public function comment()
    {
        return $this->belongsTo(IndexedContentComment::class);
    }
}
