<?php

namespace App\Jobs;

use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use App\Models\IndexingWorkflowItem;

class PrepareContentChunks implements ShouldQueue
{
    use Queueable;
    use Batchable;

    public function __construct(
        private IndexingWorkflowItem $indexingItem,
    ) {
    }

    public function handle(): void
    {
        // Wait for IndexFile job to complete by checking if chunks exist
        $maxAttempts = 30; // 30 seconds max wait
        $attempt = 0;

        while ($attempt < $maxAttempts) {
            $this->indexingItem->refresh();

            if ($this->indexingItem->status === 'prepared' &&
                $this->indexingItem->indexed_content->chunks()->exists()) {
                return; // Chunks are ready
            }

            if (in_array($this->indexingItem->status, ['warning', 'failed'])) {
                // IndexFile job failed, nothing to prepare
                return;
            }

            sleep(1);
            $attempt++;
        }

        // If we get here, something went wrong
        $this->indexingItem->update([
            'status' => 'warning',
            'error_message' => 'Timeout waiting for content chunks to be prepared',
        ]);
    }
}
