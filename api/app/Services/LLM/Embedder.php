<?php

namespace App\Services\LLM;

interface Embedder
{
    public function createEmbedding(string $text): array;
}
