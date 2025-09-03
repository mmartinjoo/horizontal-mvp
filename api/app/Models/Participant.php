<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class Participant extends Model
{
    protected $guarded = [];

    protected $casts = [
        'embedding' => 'array',
    ];

    public function document_chunks(): MorphToMany
    {
        return $this->morphToMany(DocumentChunk::class, 'entity', 'documents_participants');
    }

    public function document_comments(): MorphToMany
    {
        return $this->morphToMany(DocumentComment::class, 'entity', 'documents_participants');
    }

    public function entity(): MorphToMany
    {
        return $this->morphToMany(Model::class, 'entity', 'documents_participants');
    }
}
