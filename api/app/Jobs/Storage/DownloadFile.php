<?php

namespace App\Jobs\Storage;

use App\Exceptions\Storage\FileDownloadException;
use App\Integrations\Storage\GoogleDrive;
use Exception;
use Google\Client;
use Google\Service\Drive;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use League\Flysystem\FileAttributes;
use League\Flysystem\Filesystem;
use Masbug\Flysystem\GoogleDriveAdapter;

class DownloadFile implements ShouldQueue
{
    use Queueable;

    public function __construct(private $file)
    {
    }

    public function handle(GoogleDrive $drive): void
    {
        $drive->downloadFile($this->file);
    }
}
