<?php

namespace App\Jobs;

use App\Models\IndexingWorkflowItem;
use App\Services\LLM\Embedder;
use App\Services\VectorStore\VectorStore;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class CreateEmbeddingJob implements ShouldQueue
{
    use Queueable;
    use Batchable;

    public function __construct(
        private IndexingWorkflowItem $indexingItem,
    ) {
    }

    public function handle(Embedder $embedder, VectorStore $vectorStore): void
    {
        $this->indexingItem->update([
            'status' => 'vectorizing',
        ]);
        $embedding = $embedder->createEmbedding($this->indexingItem->indexed_content->getEmbeddableContent());
        $vectorStore->upsert($this->indexingItem->indexed_content, $embedding);
        $this->indexingItem->update([
            'status' => 'vectorized',
        ]);
    }
}
