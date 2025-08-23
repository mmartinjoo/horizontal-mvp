<?php

namespace App\Jobs;

use App\Integrations\Storage\GoogleDrive;
use App\Models\User;
use App\Services\Indexing\FilePrioritizer;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SyncGoogleDrive implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private User $user,
    ) {
    }

    public function handle(GoogleDrive $drive, FilePrioritizer $prioritizer): void
    {
        $files = $drive->listDirectoryContents('deQenQ');
        $contents = $prioritizer->prioritize2($files);
        foreach ($contents['high'] as $file) {
            IndexFile::dispatch($this->user, $file, 'high');
        }

        foreach ($contents['medium'] as $file) {
            IndexFile::dispatch($this->user, $file, 'medium');
//                ->delay(now()->addHours(1));
        }

        foreach ($contents['low'] as $file) {
            IndexFile::dispatch($this->user, $file, 'low');
//                ->delay(now()->addHours(4));
        }
    }
}
