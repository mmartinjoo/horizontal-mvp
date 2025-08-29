<?php

namespace App\Models;

use App\Exceptions\NoEmbeddingsException;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

class Document extends Model implements Embeddable
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
        return $this->hasMany(DocumentChunk::class);
    }

    public function comments()
    {
        return $this->hasMany(DocumentComment::class);
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

    public function getParticipants(): Collection
    {
        $participants = collect();
        if ($this->source_type === 'jira') {
            $participants[] = Arr::get($this->metadata, 'fields.assignee.displayName');

            foreach ($this->comments as $comment) {
                $participants[] = $comment->author;
            }
        }
        return $participants->unique();
    }
}
