<?php

namespace App\Jobs;

use App\Integrations\Storage\GoogleDrive;
use App\Models\IndexedContent;
use App\Models\IndexingWorkflow;
use App\Models\IndexingWorkflowItem;
use App\Models\User;
use App\Services\Indexing\FilePrioritizer;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

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
        $files = $drive->listDirectoryContents();
        $contents = $prioritizer->prioritize2($files);
        $indexing->update([
            'status' => 'downloaded',
            'overall_items' => count($contents['high']) + count($contents['medium']) + count($contents['low']),
        ]);

        foreach (['high', 'medium', 'low'] as $prio) {
            foreach ($contents[$prio] as $file) {
                $content = IndexedContent::create([
                    'user_id' => $this->user->id,
                    'team_id' => $this->user->team_id,
                    'source_type' => 'google_drive',
                    'source_id' => $file->extraMetadata()['id'],
                    'title' => $file->path(),
                    'metadata' => $file,
                    'priority' => $prio,
                ]);
                $indexingItem = IndexingWorkflowItem::create([
                    'indexing_workflow_id' => $indexing->id,
                    'data' => $file,
                    'status' => 'queued',
                    'indexed_content_id' => $content->id,
                ]);
                IndexFile::dispatch($indexingItem->id, $file, $prio);
            }
        }
    }
}
