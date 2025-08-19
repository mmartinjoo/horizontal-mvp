<?php

namespace App\Services\VectorStore;

use App\Models\Embeddable;

class Pinecone implements VectorStore
{
    public function __construct(
        private readonly string $apiKey
    ) {
    }

    public function upsert(Embeddable $embeddable, array $embedding): void
    {
    }
}
