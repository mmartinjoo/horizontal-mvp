<?php

namespace App\Services\Indexing;

use App\Integrations\Storage\GoogleDrive;
use App\Jobs\Storage\DownloadFile;
use Illuminate\Support\Collection;
use League\Flysystem\FileAttributes;

class Synchronizer
{
    /**
     * @param $files array<string, Collection<FileAttributes>>
     */
    public function syncStorage(array $files)
    {
        /** @var FileAttributes $file */
        foreach ($files['high'] as $file) {
            DownloadFile::dispatch($file);
        }
    }
}
