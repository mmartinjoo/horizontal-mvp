<?php

namespace App\Jobs;

use App\Models\IndexedContent;
use App\Services\LLM\Embedder;
use App\Services\VectorStore\VectorStore;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class CreateEmbeddingJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly IndexedContent $content,
        private readonly VectorStore    $vectorStore,
    ) {
    }

    public function handle(Embedder $embedder): void
    {
        $embedding = $embedder->createEmbedding($this->content->body);
        $this->vectorStore->upsert($this->content, $embedding);
//        $this->content->update([
//            'embedding' => $embedding,
//        ]);
    }
}
