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

            Bus::batch([
                new IndexFile($indexingItem, $file, 'high'),
                new EmbedContentJob($indexingItem),
            ])
                ->then(function () use ($indexingItem) {
                    $indexingItem->update([
                        'status' => 'completed',
                    ]);
                })
                ->finally(function () use ($indexingItem) {
                    $indexingItem->update([
                        'status' => 'completed',
                    ]);
                })
                ->catch(function (Batch $batch, Throwable $e) use ($indexingItem) {
                    $indexingItem->update([
                        'status' => 'failed',
                        'error_message' => $e->getMessage(),
                    ]);
                })
                ->dispatch();
        }

//        foreach ($contents['medium'] as $file) {
//            IndexFile::dispatch($this->user, $file, 'medium')
//                ->delay(now()->addHours(1));
//        }
//
//        foreach ($contents['low'] as $file) {
//            IndexFile::dispatch($this->user, $file, 'low')
//                ->delay(now()->addHours(4));
//        }
    }
}
