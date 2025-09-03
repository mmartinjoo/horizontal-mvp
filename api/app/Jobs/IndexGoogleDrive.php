<?php

namespace App\Jobs;

use App\Integrations\Storage\File;
use App\Integrations\Storage\GoogleDrive;
use App\Models\Document;
use App\Models\IndexingWorkflow;
use App\Models\IndexingWorkflowItem;
use App\Models\Team;
use App\Services\GraphDB\GraphDB;
use App\Services\Indexing\FilePrioritizer;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class IndexGoogleDrive implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private Team $team,
    ) {
    }

    public function handle(
        GoogleDrive $drive,
        FilePrioritizer $prioritizer,
        GraphDB $graphDB,
    ): void {
        /** @var IndexingWorkflow $indexing */
        $indexing = IndexingWorkflow::create([
            'integration' => 'google_drive',
            'status' => 'downloading',
            'team_id' => $this->team->id,
            'job_id' => $this->job->payload()['uuid'],
        ]);
        $files = $drive->listDirectoryContents('horizontal.app');
        $contents = $prioritizer->prioritize($files);
        $indexing->update([
            'status' => 'downloaded',
            'overall_items' => count($contents['high']) + count($contents['medium']) + count($contents['low']),
        ]);

        foreach (['high', 'medium', 'low'] as $prio) {
            foreach ($contents[$prio] as $i => $file) {
                if (!$this->fileNeedsIndexing($file)) {
                    $indexing->increment('skipped_items', 1);
                    if ($i === count($contents[$prio]) - 1) {
                        $indexing->update([
                            'status' => 'completed',
                        ]);
                    }
                    continue;
                }
                $count = Document::query()
                    ->where('team_id', $this->team->id)
                    ->where('source_type', 'google_drive')
                    ->where('source_id', $file->extraMetadata()['id'])
                    ->delete();

                $indexing->increment('deleted_items', $count);
                $document = Document::create([
                    'team_id' => $this->team->id,
                    'source_type' => 'google_drive',
                    'source_id' => $file->extraMetadata()['id'],
                    'title' => $file->path(),
                    'metadata' => $file,
                    'priority' => $prio,
                ]);
                $graphDB->createNode('File', [
                    'id' => $document->id,
                    'name' => $document->title,
                ]);
                $indexingItem = IndexingWorkflowItem::create([
                    'indexing_workflow_id' => $indexing->id,
                    'data' => $file,
                    'status' => 'queued',
                    'document_id' => $document->id,
                ]);
                IndexFile::dispatch($indexingItem->id, $file);
            }
        }
    }

    private function fileNeedsIndexing(File $file): bool
    {
        $existingContent = Document::query()
            ->where('source_id', $file->extraMetadata()['id'])
            ->first();

        if ($existingContent === null) {
            return true;
        }

        return $file->getUpdatedAt()->gt($existingContent->indexed_at);
    }
}
