<?php

namespace App\Services\Indexing;

use App\Jobs\EmbedContentChunkJob;
use App\Models\IndexingWorkflow;
use Illuminate\Bus\Batch;
use Illuminate\Support\Facades\Bus;
use Throwable;

/**
 * We need this class for performance optimization reasons. There are two other solutions:
 *  - Keep this as a function in the original job. That won't work because `$this` cannot be called in a `then` etc callback
 *  - It can be a callback inside a dedicated class that creates circular references (large arrays -> this function -> callbacks, etc) and causes issues in garbage collection resulting in memory leaks
 */
class EmbeddingDispatcher
{
    public function __construct()
    {
    }

    public function dispatch(int $indexingWorkflowId): void
    {
        $indexing = IndexingWorkflow::findOrFail($indexingWorkflowId);
        $embeddingJobs = [];
        foreach ($indexing->items as $item) {
            $item->refresh();
            if ($item->status === 'prepared') {
                foreach ($item->indexed_content->chunks as $chunk) {
                    $embeddingJobs[] = new EmbedContentChunkJob($chunk, $item->id);
                }
            }
        }

        if (!empty($embeddingJobs)) {
            Bus::batch($embeddingJobs)
                ->name("embedding_workflow_{$indexing->id}")
                ->then(function (Batch $batch) use ($indexing) {
                    // Mark all items as completed and workflow as finished
                    foreach ($indexing->items as $item) {
                        if ($item->status === 'prepared') {
                            $item->update(['status' => 'completed']);
                        }
                    }
                    $indexing->update(['status' => 'finished']);
                })
                ->catch(function (Batch $batch, Throwable $e) use ($indexing) {
                    $indexing->update(['status' => 'finished']);
                })
                ->dispatch();
        } else {
            $indexing->update(['status' => 'finished']);
        }
    }
}
