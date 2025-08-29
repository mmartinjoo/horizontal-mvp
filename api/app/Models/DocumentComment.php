<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DocumentComment extends Model implements Embeddable
{
    use HasEmbedding;

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

    public function entities(): HasMany
    {
        return $this->hasMany(DocumentCommentEntity::class);
    }

    public function getEmbeddableContent(): string
    {
        return $this->author . ' commented: ' . $this->body;
    }
}
