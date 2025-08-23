<?php

namespace App\Jobs;

use App\Integrations\Storage\GoogleDrive;
use App\Jobs\Storage\DownloadFile;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SyncGoogleDrive implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private User $user,
    ) {
    }

    public function handle(GoogleDrive $drive): void
    {
        $contents = $drive->listDirectoryContents('deQenQ');
        foreach ($contents['high'] as $file) {
            IndexFile::dispatch($this->user, $file, 'high');
//            DownloadFile::dispatch($file);
        }

//        foreach ($contents['medium'] as $file) {
//            DownloadFile::dispatch($file)
//                ->delay(now()->addHours(1));
//        }
//
//        foreach ($contents['low'] as $file) {
//            DownloadFile::dispatch($file)
//                ->delay(now()->addHours(4));
//        }
    }
}
