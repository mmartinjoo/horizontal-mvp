<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

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

    public function entities(): HasOne
    {
        return $this->hasOne(DocumentChunkEntity::class);
    }

    public function getEmbeddableContent(): string
    {
        return $this->body;
    }
}
