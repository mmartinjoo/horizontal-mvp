<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Topic extends Model
{
    protected $guarded = [];

    protected $casts = [
        'variations' => 'array',
        'embedding' => 'array',
    ];

    public function related_topics()
    {
        return $this->belongsToMany(Topic::class, 'topic_relations', 'topic_id', 'related_topic_id')
            ->withPivot('similarity');
    }

    public function reverse_related_topics()
    {
        return $this->belongsToMany(Topic::class, 'topic_relations', 'related_topic_id', 'topic_id')
            ->withPivot('similarity');
    }

    public function allRelatedTopics()
    {
        return $this->related_topics()->get()->merge($this->reverse_related_topics()->get())->unique('id');
    }
}
