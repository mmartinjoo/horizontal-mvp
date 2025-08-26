<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IndexedContentComment extends Model implements Embeddable
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

    public function content()
    {
        return $this->belongsTo(IndexedContent::class);
    }

    public function getEmbeddableContent(): string
    {
        return $this->author . ' commented: ' . $this->body;
    }
}
