<?php

namespace App\Models;

trait HasEmbedding
{
    public function getEmbedding(): array
    {
        return $this->embedding;
    }

    public function updateEmbedding(array $embedding): void
    {
        $this->embedding = $embedding;
        $this->save();
    }
}
