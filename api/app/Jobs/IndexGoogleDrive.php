<?php

namespace App\Jobs;

use App\Integrations\Storage\GoogleDrive;
use App\Models\IndexedContent;
use App\Models\IndexingWorkflow;
use App\Models\IndexingWorkflowItem;
use App\Models\User;
use App\Services\Indexing\FilePrioritizer;
use Illuminate\Bus\Batch;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Bus;
use Throwable;

class IndexGoogleDrive implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private User $user,
    ) {
    }

    public function handle(GoogleDrive $drive, FilePrioritizer $prioritizer): void
    {
        $indexing = IndexingWorkflow::create([
            'integration' => 'google_drive',
            'status' => 'downloading',
            'user_id' => $this->user->id,
            'job_id' => $this->job->getJobId(),
        ]);
        $files = $drive->listDirectoryContents('deQenQ');
        $indexing->update([
            'status' => 'downloaded',
        ]);
        $contents = $prioritizer->prioritize2($files);
        $indexing->update([
            'status' => 'in_progress',
            'overall_items' => count($contents['high']) + count($contents['medium']) + count($contents['low']),
        ]);

        $allJobs = [];
        $workflowItems = [];

        foreach ($contents['high'] as $file) {
            $content = IndexedContent::create([
                'user_id' => $this->user->id,
                'team_id' => $this->user->team_id,
                'source_type' => 'google_drive',
                'source_id' => $file->extraMetadata()['id'],
                'title' => $file->path(),
                'metadata' => $file,
                'priority' => 'high',
            ]);
            $indexingItem = IndexingWorkflowItem::create([
                'indexing_workflow_id' => $indexing->id,
                'data' => $file,
                'status' => 'queued',
                'indexed_content_id' => $content->id,
            ]);
            $workflowItems[] = $indexingItem;
            $allJobs[] = new IndexFile($indexingItem, $file, 'high');
            $allJobs[] = new PrepareContentChunks($indexingItem);
        }

        $dispatchEmbeddingJobs = function () use ($indexing, $workflowItems) {
            $embeddingJobs = [];
            foreach ($workflowItems as $item) {
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
                    ->then(function (Batch $batch) use ($indexing, $workflowItems) {
                        // Mark all items as completed and workflow as finished
                        foreach ($workflowItems as $item) {
                            if ($item->status === 'prepared') {
                                $item->update(['status' => 'completed']);
                            }
                        }
                        $indexing->update(['status' => 'finished']);
                    })
                    ->catch(function (Batch $batch, Throwable $e) use ($indexing, $workflowItems) {
                        $indexing->update(['status' => 'finished']);
                    })
                    ->dispatch();
            } else {
                $indexing->update(['status' => 'finished']);
            }
        };

        Bus::batch($allJobs)
            ->name("indexing_workflow_{$indexing->id}")
            ->then(function (Batch $batch) use ($dispatchEmbeddingJobs, $indexing) {
                $dispatchEmbeddingJobs();
            })
            ->catch(function (Batch $batch, Throwable $e) use ($indexing, $workflowItems) {
                $indexing->update(['status' => 'warning']);
            })
            ->finally(function () use ($dispatchEmbeddingJobs) {
                $dispatchEmbeddingJobs();
            })
            ->dispatch();
    }
}
