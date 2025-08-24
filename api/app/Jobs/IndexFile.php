<?php

namespace App\Jobs;

use App\Exceptions\IndexingException;
use App\Exceptions\NoContentToIndexException;
use App\Exceptions\Storage\FileDownloadException;
use App\Integrations\Storage\File;
use App\Integrations\Storage\GoogleDrive;
use App\Models\IndexedContentChunk;
use App\Models\IndexingWorkflowItem;
use App\Services\Indexing\TextChunker;
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

    public function handle(GoogleDrive $drive, TextChunker $textChunker): void
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

                // TODO: update status of indexing item
                if (strlen($content) === 0) {
                    $this->indexingItem->update([
                        'status' => 'warning',
                    ]);
                    if ($this->file->mimeType() === 'application/pdf') {
                        throw new NoContentToIndexException('Unable to parse PDF: ' . json_encode($this->file));
                    }

                    throw new NoContentToIndexException('File is empty: ' . json_encode($this->file));
                }

                $chunks = $textChunker->chunk($content);
                if (count($chunks) === 0) {
                    $this->indexingItem->update([
                        'status' => 'warning',
                    ]);
                    throw new NoContentToIndexException('Chunk is empty: ' . json_encode($this->file) . '; content: ' . $content);
                }
                if (count($chunks) === 1 && strlen(trim($chunks->first())) === 0) {
                    $this->indexingItem->update([
                        'status' => 'warning',
                    ]);
                    throw new NoContentToIndexException('Chunk contains one empty item: ' . json_encode($this->file) . '; content: ' . $content);
                }

                foreach ($chunks as $i => $chunk) {
                    IndexedContentChunk::create([
                        'indexed_content_id' => $this->indexingItem->indexed_content->id,
                        'body' => $chunk,
                        'position' => $i+1,
                    ]);
                }

                $this->indexingItem->indexed_content()->update([
                    'preview' => $chunks->first(),
                    'indexed_at' => now(),
                    'priority' => $this->priority,
                ]);
                $this->indexingItem->update([
                    'status' => 'prepared',
                ]);
                return;
            }
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
