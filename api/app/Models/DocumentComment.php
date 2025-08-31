<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class DocumentComment extends Model implements Embeddable
{
    use HasEmbedding;
    use HasParticipants;
    use HasTopics;

    protected $guarded = [];

    protected $hidden = [
        'embedding',
        'search_vector',
    ];

    protected $casts = [
        'embedding' => 'array',
        'metadata' => 'array',
        'commented_at' => 'datetime',
    ];

    public function document()
    {
        return $this->belongsTo(Document::class);
    }

    public function getEmbeddableContent(): string
    {
        return $this->author . ' commented: ' . $this->body;
    }
}
