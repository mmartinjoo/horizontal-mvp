<?php

namespace App\Jobs;

use App\Models\IndexingWorkflowItem;
use App\Services\LLM\Embedder;
use App\Services\VectorStore\VectorStore;
use Illuminate\Bus\Batch;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Bus;
use Throwable;

class EmbedContentJob implements ShouldQueue
{
    use Queueable;
    use Batchable;

    public function __construct(
        private IndexingWorkflowItem $indexingItem,
    ) {
    }

    public function handle(): void
    {
        $this->indexingItem->update([
            'status' => 'vectorizing',
        ]);

        $jobs = [];
        foreach ($this->indexingItem->indexed_content->chunks as $chunk) {
            $jobs[] = new EmbedContentChunkJob($chunk);
        }

        Bus::batch($jobs)
            ->then(function () {
                $this->indexingItem->update([
                    'status' => 'completed',
                ]);
            })
            ->catch(function (Batch $batch, Throwable $e) {
                $this->indexingItem->update([
                    'status' => 'warning',
                    'error_message' => $e->getMessage(),
                ]);
            })
            ->dispatch();
    }
}
