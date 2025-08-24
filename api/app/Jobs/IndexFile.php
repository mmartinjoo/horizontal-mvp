<?php

namespace App\Jobs;

use App\Exceptions\NoContentToIndexException;
use App\Integrations\Storage\File;
use App\Integrations\Storage\GoogleDrive;
use App\Models\IndexedContentChunk;
use App\Models\IndexingWorkflowItem;
use App\Services\Indexing\TextChunker;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;

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

    public function handle(GoogleDrive $drive, TextChunker $textChunker, \App\Services\PdfParser $pdfParser): void
    {
        try {
            $jobIds = $this->indexingItem->job_ids;
            $jobIds[] = $this->job->payload()['uuid'];

            $this->indexingItem->update([
                'status' => 'downloading',
                'job_ids' => $jobIds,
            ]);

            $drive->downloadFile($this->file);
            $this->indexingItem->update([
                'status' => 'downloaded',
            ]);

            if ($this->file->mimeType() === 'application/pdf') {
                $this->indexPDF($pdfParser, $textChunker);
                return;
            } else {
                $content = Storage::read($this->file->path());
            }

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
        } finally {
            Storage::delete($this->file->path());
        }
    }

    private function indexPDF(\App\Services\PdfParser $pdfParser, TextChunker $textChunker)
    {
        $this->indexingItem->update([
            'status' => 'parsing',
        ]);

        $blocks = $pdfParser->stream($this->file->path());
        $firstChunk = '';

        $this->indexingItem->update([
            'status' => 'parsed',
        ]);

        /** @var string $block */
        foreach ($blocks as $block) {
            $chunks = $textChunker->chunk($block);
            foreach ($chunks as $i => $chunk) {
                if ($i === 0) {
                    $firstChunk = $chunk;
                }
                IndexedContentChunk::create([
                    'indexed_content_id' => $this->indexingItem->indexed_content->id,
                    'body' => $chunk,
                    'position' => $i+1,
                ]);
            }
        }

        $this->indexingItem->indexed_content()->update([
            'preview' => $firstChunk,
            'indexed_at' => now(),
            'priority' => $this->priority,
        ]);
        $this->indexingItem->update([
            'status' => 'prepared',
        ]);
    }
}
