<?php

namespace App\Services\VectorStore;

use App\Models\Embeddable;

interface VectorStore
{
    public function upsert(Embeddable $embeddable, array $embedding): void;
}
