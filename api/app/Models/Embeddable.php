<?php

namespace App\Models;

interface Embeddable
{
    public function getEmbedding(): array;

    public function updateEmbedding(array $embedding): void;

    public function getEmbeddableContent(): string;
}
