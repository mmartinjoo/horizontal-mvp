<?php

namespace App\Jobs;

use App\Models\IndexingWorkflowItem;
use App\Services\LLM\Embedder;
use App\Services\VectorStore\VectorStore;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Bus;

class EmbedContentJob implements ShouldQueue
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

        $jobs = [];
        foreach ($this->indexingItem->indexed_content->chunks as $chunk) {
            $jobs[] = new EmbedContentChunkJob($chunk);
        }

        Bus::batch($jobs)
            ->dispatch();
    }
}
