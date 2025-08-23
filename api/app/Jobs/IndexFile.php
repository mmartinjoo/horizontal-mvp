<?php

namespace App\Jobs;

use App\Exceptions\IndexingException;
use App\Exceptions\Storage\FileDownloadException;
use App\Integrations\Storage\File;
use App\Integrations\Storage\GoogleDrive;
use App\Models\IndexedContent;
use App\Models\User;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use League\Flysystem\FileAttributes;
use Smalot\PdfParser\Parser;

class IndexFile implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private User $user,
        private File $file,
        private string $priority,
    ) {
    }

    public function handle(GoogleDrive $drive): void
    {
        try {
            $drive->downloadFile($this->file);

            if ($this->priority === 'high') {
                if ($this->file->mimeType() === 'application/pdf') {
                    $content = $this->parsePDF(Storage::read($this->file->path()));
                } else {
                    $content = Storage::read($this->file->path());
                }

                IndexedContent::create([
                    'user_id' => $this->user->id,
                    'team_id' => $this->user->team_id,
                    'source_type' => 'google_drive',
                    'source_id' => $this->file->extraMetadata()['id'],
                    'title' => $this->file->path(),
                    'body' => $content,
                    'metadata' => $this->file,
                    'indexed_at' => now(),
                    'priority' => 'high'
                ]);
                return;
            }

            if ($this->priority === 'medium' || $this->priority === 'low') {
                $preview = $this->readFirstMB();
                if ($this->file->mimeType() === 'application/pdf') {
                    $preview = $this->parsePDF($preview);
                }

                IndexedContent::create([
                    'user_id' => $this->user->id,
                    'team_id' => $this->user->team_id,
                    'source_type' => 'google_drive',
                    'source_id' => $this->file->extraMetadata()['id'],
                    'title' => $this->file->path(),
                    'preview' => $preview,
                    'metadata' => $this->file,
                    'indexed_at' => now(),
                    'priority' => $this->priority,
                ]);
                return;
            }
        } catch (Exception $ex) {
            throw new IndexingException('Failed to index file: ' . json_encode($this->file), 0, $ex);
        } finally {
            Storage::delete($this->file->path());
        }
    }

    private function parsePDF(string $raw): string
    {
        $parser = new Parser;
        $pdf = $parser->parseContent($raw);
        return $pdf->getText();
    }

    private function readFirstMB(): string
    {
        $stream = null;

        try {
            $stream = Storage::readStream($this->file->path());
            if ($stream === false) {
                throw new FileDownloadException("Failed to open stream for file: " . json_encode($this->file));
            }
            $content = fread($stream, 1*1024*1024);
            if ($content === false) {
                throw new FileDownloadException("Failed to read first MB of file: " . json_encode($this->file));
            }
            return $content;
        } finally {
            if (is_resource($stream)) {
                fclose($stream);
            }
        }
    }
}
