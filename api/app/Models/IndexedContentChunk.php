<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class IndexedContentChunk extends Model implements Embeddable
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

    public function content()
    {
        return $this->belongsTo(IndexedContent::class);
    }

    public function entities(): HasMany
    {
        return $this->hasMany(IndexedContentChunkEntity::class);
    }

    public function getEmbeddableContent(): string
    {
        return $this->body;
    }
}
