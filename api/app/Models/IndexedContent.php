<?php

namespace App\Models;

use App\Exceptions\NoEmbeddingsException;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IndexedContent extends Model implements Embeddable
{
    use HasFactory;
    use HasEmbedding;

    protected $guarded = [];

    protected $hidden = [
        'embedding',
        'search_vector',
    ];

    protected $casts = [
        'metadata' => 'array',
        'embedding' => 'array',
        'indexed_at' => 'datetime',
    ];

    public function chunks()
    {
        return $this->hasMany(IndexedContentChunk::class);
    }

    public function getEmbeddableContent(): string
    {
        if (!$this->body && !$this->preview) {
            throw new NoEmbeddingsException("No content to embed: " . json_encode($this->attributes));
        }
        if (!$this->body) {
            return $this->preview;
        }
        return $this->body;
    }
}
