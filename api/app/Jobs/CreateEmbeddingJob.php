<?php

namespace App\Jobs;

use App\Models\Embeddable;
use App\Services\LLM\Embedder;
use App\Services\VectorStore\VectorStore;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class CreateEmbeddingJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly Embeddable  $content,
        private readonly VectorStore $vectorStore,
    ) {
    }

    public function handle(Embedder $embedder): void
    {
        $embedding = $embedder->createEmbedding($this->content->getEmbeddableContent());
        $this->vectorStore->upsert($this->content, $embedding);
    }
}
