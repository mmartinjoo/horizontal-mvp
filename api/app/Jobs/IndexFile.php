<?php

namespace App\Jobs;

use App\Exceptions\IndexingException;
use App\Exceptions\Storage\FileDownloadException;
use App\Integrations\Storage\File;
use App\Integrations\Storage\GoogleDrive;
use App\Models\IndexedContent;
use App\Models\IndexingWorkflowItem;
use Exception;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use Smalot\PdfParser\Parser;

class IndexFile implements ShouldQueue
{
    use Queueable;
    use Batchable;

    public function __construct(
        private IndexingWorkflowItem $indexingItem,
        private File $file,
        private string $priority,
    ) {
    }

    public function handle(GoogleDrive $drive): void
    {
        try {
            $jobIds = $this->indexingItem->job_ids;
            $jobIds[] = $this->job->getJobId();

            $this->indexingItem->update([
                'status' => 'downloading',
                'job_ids' => $jobIds,
            ]);

            $drive->downloadFile($this->file);
            $this->indexingItem->update([
                'status' => 'downloaded',
            ]);

            if ($this->priority === 'high') {
                if ($this->file->mimeType() === 'application/pdf') {
                    $this->indexingItem->update([
                        'status' => 'parsing',
                    ]);
                    $content = $this->parsePDF(Storage::read($this->file->path()));
                    $this->indexingItem->update([
                        'status' => 'parsed',
                    ]);
                } else {
                    $content = Storage::read($this->file->path());
                }

                $this->indexingItem->indexed_content()->update([
                    'body' => $content,
                    'indexed_at' => now(),
                    'priority' => 'high',
                ]);
                $this->indexingItem->update([
                    'status' => 'prepared',
                ]);
                return;
            }

            if ($this->priority === 'medium' || $this->priority === 'low') {
                $this->indexingItem->update([
                    'status' => 'parsing_preview',
                ]);
                $preview = $this->readFirstMB();
                if ($this->file->mimeType() === 'application/pdf') {
                    $preview = $this->parsePDF($preview);
                }
                $this->indexingItem->update([
                    'status' => 'preview_parsed',
                ]);

                $this->indexingItem->indexed_content()->update([
                    'preview' => $preview,
                    'indexed_at' => now(),
                ]);
                $this->indexingItem->update([
                    'status' => 'prepared',
                ]);
                return;
            }
        } /*catch (Exception $ex) {
            $this->indexingItem->update([
                'status' => 'failed',
                'error_message' => $ex->getMessage(),
            ]);
            throw new IndexingException('Failed to index file: ' . json_encode($this->file), 0, $ex);*/
        finally {
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
