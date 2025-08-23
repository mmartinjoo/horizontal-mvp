<?php

namespace App\Jobs;

use App\Integrations\Storage\GoogleDrive;
use App\Models\IndexedContent;
use App\Models\User;
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
        private FileAttributes $file,
        private string $priority,
    ) {
    }

    public function handle(GoogleDrive $drive): void
    {
        $drive->downloadFile($this->file);
        if ($this->priority === 'high') {
            if ($this->file->mimeType() === 'application/pdf') {
                $content = $this->parsePdf(Storage::read($this->file->path()));
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
            ]);

            Storage::delete($this->file->path());
        }
    }

    private function parsePdf(string $raw): string
    {
        $parser = new Parser;
        $pdf = $parser->parseContent($raw);
        return $pdf->getText();
    }
}
