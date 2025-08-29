<?php

namespace App\Jobs;

use App\Models\IndexingWorkflowItem;
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
        private int $indexingWorkflowItemId,
    ) {
    }

    public function handle(): void
    {
        $indexingWorkflowItem = IndexingWorkflowItem::findOrFail($this->indexingWorkflowItemId);
        $indexingWorkflowItem->update([
            'status' => 'vectorizing',
        ]);

        $jobs = [];
        foreach ($indexingWorkflowItem->document->chunks as $chunk) {
            $jobs[] = new EmbedContentChunkJob($chunk, $indexingWorkflowItem->id);
        }

        Bus::batch($jobs)
            ->then(function () use ($indexingWorkflowItem) {
                $indexingWorkflowItem->update([
                    'status' => 'completed',
                ]);
            })
            ->catch(function (Batch $batch, Throwable $e) use ($indexingWorkflowItem) {
                $indexingWorkflowItem->update([
                    'status' => 'warning',
                    'error_message' => $e->getMessage(),
                ]);
            })
            ->dispatch();
    }
}
