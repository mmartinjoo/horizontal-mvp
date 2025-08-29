<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DocumentChunk extends Model implements Embeddable
{
    use HasEmbedding;

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

    public function entities(): HasMany
    {
        return $this->hasMany(DocumentChunkEntity::class);
    }

    public function getEmbeddableContent(): string
    {
        return $this->body;
    }
}
