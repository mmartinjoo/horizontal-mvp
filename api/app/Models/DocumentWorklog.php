<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentWorklog extends Model implements Embeddable
{
    use HasEmbedding;
    use HasParticipants;
    use HasTopics;

    protected $guarded = [];

    protected $casts = [
        'metadata' => 'array',
        'embedding' => 'array',
    ];

    public function getEmbeddableContent(): string
    {
        return $this->description;
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }
}
