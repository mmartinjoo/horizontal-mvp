<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class DocumentChunk extends Model implements Embeddable
{
    use HasEmbedding;
    use HasParticipants;
    use HasTopics;

    protected $guarded = [];

    protected $casts = [
        'embedding' => 'array',
    ];

    protected $hidden = [
        'embedding',
        'search_vector',
    ];


    public function document()
    {
        return $this->belongsTo(Document::class);
    }

    protected static function booted()
    {
        static::deleting(function (DocumentChunk $chunk) {
            DocumentParticipant::query()
                ->where('entity_id', $chunk->id)
                ->where('entity_type', get_class($chunk))
                ->delete();

            DocumentTopic::query()
                ->where('entity_id', $chunk->id)
                ->where('entity_type', get_class($chunk))
                ->delete();
        });
    }

    public function getEmbeddableContent(): string
    {
        return $this->body;
    }
}
