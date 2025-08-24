<?php

namespace App\Jobs;

use App\Integrations\Storage\GoogleDrive;
use App\Models\IndexedContent;
use App\Models\IndexingWorkflow;
use App\Models\IndexingWorkflowItem;
use App\Models\User;
use App\Services\Indexing\EmbeddingDispatcher;
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

    public function handle(GoogleDrive $drive, FilePrioritizer $prioritizer, EmbeddingDispatcher $embeddingDispatcher): void
    {
        $indexing = IndexingWorkflow::create([
            'integration' => 'google_drive',
            'status' => 'downloading',
            'user_id' => $this->user->id,
            'job_id' => $this->job->getJobId(),
        ]);
        $files = $drive->listDirectoryContents();
        $contents = $prioritizer->prioritize2($files);
        $indexing->update([
            'status' => 'downloaded',
            'overall_items' => count($contents['high']) + count($contents['medium']) + count($contents['low']),
        ]);

        $allJobs = [];
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
            $allJobs[] = new IndexFile($indexingItem, $file, 'high');
            $allJobs[] = new PrepareContentChunks($indexingItem);
        }

        Bus::batch($allJobs)
            ->name("indexing_workflow_{$indexing->id}")
            ->then(function (Batch $batch) use ($indexing, $embeddingDispatcher) {
                $embeddingDispatcher->dispatch($indexing->id);
            })
            ->catch(function (Batch $batch, Throwable $e) use ($indexing) {
                $indexing->update(['status' => 'warning']);
            })
            ->finally(function () use ($indexing, $embeddingDispatcher) {
                $embeddingDispatcher->dispatch($indexing->id);
            })
            ->dispatch();
    }
}
