<?php

namespace App\Services\VectorStore;

use App\Models\Embeddable;

class Postgres implements VectorStore
{
    public function upsert(Embeddable $embeddable, array $embedding): void
    {
        $embeddable->updateEmbedding($embedding);
    }
}
