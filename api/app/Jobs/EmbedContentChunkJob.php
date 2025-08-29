<?php

namespace App\Jobs;

use App\Models\DocumentChunk;
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
        private DocumentChunk $chunk,
        private int           $workflowItemId,
    ) {
    }

    public function handle(Embedder $embedder, VectorStore $vectorStore): void
    {
        $workflowItem = IndexingWorkflowItem::find($this->workflowItemId);
        if ($workflowItem && $workflowItem->status === 'prepared') {
            $workflowItem->update(['status' => 'vectorizing']);
        }

        $embedding = $embedder->createEmbedding($this->chunk->getEmbeddableContent());
        $vectorStore->upsert($this->chunk, $embedding);
    }
}
