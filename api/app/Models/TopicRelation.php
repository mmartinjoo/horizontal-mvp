<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TopicRelation extends Model
{
    protected $guarded = [];

    public function topic(): BelongsTo
    {
        return $this->belongsTo(Topic::class, 'topic_id');
    }

    public function related_topic(): BelongsTo
    {
        return $this->belongsTo(Topic::class, 'related_topic_id');
    }
}
