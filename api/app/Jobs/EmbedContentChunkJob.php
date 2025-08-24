<?php

namespace App\Jobs;

use App\Models\IndexedContentChunk;
use App\Models\IndexingWorkflowItem;
use App\Services\LLM\Embedder;
use App\Services\VectorStore\VectorStore;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class EmbedContentChunkJob implements ShouldQueue
{
    use Queueable;
    use Batchable;

    public function __construct(
        private IndexedContentChunk $chunk,
    ) {
    }

    public function handle(Embedder $embedder, VectorStore $vectorStore): void
    {
        $embedding = $embedder->createEmbedding($this->chunk->getEmbeddableContent());
        $vectorStore->upsert($this->chunk, $embedding);
    }
}
