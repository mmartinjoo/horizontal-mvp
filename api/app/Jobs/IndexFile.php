<?php

namespace App\Jobs;

use App\Exceptions\NoContentToIndexException;
use App\Integrations\Storage\File;
use App\Integrations\Storage\GoogleDrive;
use App\Models\IndexedContentChunk;
use App\Models\IndexingWorkflow;
use App\Models\IndexingWorkflowItem;
use App\Services\Indexing\TextChunker;
use App\Services\LLM\Embedder;
use App\Services\PdfParser;
use App\Services\VectorStore\VectorStore;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use Throwable;

class IndexFile implements ShouldQueue
{
    use Queueable;
    use Batchable;

    public function __construct(
        private int $indexingWorkflowItemId,
        private File $file,
    ) {
    }

    public function handle(
        GoogleDrive $drive,
        TextChunker $textChunker,
        PdfParser $pdfParser,
        Embedder $embedder,
        VectorStore $vectorStore,
    ): void {
        try {
            $indexingWorkflowItem = IndexingWorkflowItem::find($this->indexingWorkflowItemId);
            $jobIds = $indexingWorkflowItem->job_ids;
            $jobIds[] = $this->job->payload()['uuid'];

            $indexingWorkflowItem->update([
                'status' => 'downloading',
                'job_ids' => $jobIds,
            ]);

            $drive->downloadFile($this->file);
            $indexingWorkflowItem->update([
                'status' => 'downloaded',
            ]);

            if ($this->file->mimeType() === 'application/pdf') {
                $this->indexPDF($pdfParser, $textChunker, $indexingWorkflowItem);
                $this->createEmbedding($indexingWorkflowItem, $embedder, $vectorStore);;
                $this->updateWorkflowStatus($indexingWorkflowItem);
                return;
            } else {
                $content = Storage::read($this->file->path());
            }

            if (strlen($content) === 0) {
                $indexingWorkflowItem->update([
                    'status' => 'warning',
                ]);
                throw new NoContentToIndexException('File is empty: ' . json_encode($this->file));
            }

            $chunks = $textChunker->chunk($content);
            if (count($chunks) === 0) {
                $indexingWorkflowItem->update([
                    'status' => 'warning',
                ]);
                throw new NoContentToIndexException('Chunk is empty: ' . json_encode($this->file) . '; content: ' . $content);
            }
            if (count($chunks) === 1 && strlen(trim($chunks->first())) === 0) {
                $indexingWorkflowItem->update([
                    'status' => 'warning',
                ]);
                throw new NoContentToIndexException('Chunk contains one empty item: ' . json_encode($this->file) . '; content: ' . $content);
            }

            foreach ($chunks as $i => $chunk) {
                IndexedContentChunk::create([
                    'indexed_content_id' => $indexingWorkflowItem->indexed_content->id,
                    'body' => $chunk,
                    'position' => $i+1,
                ]);
            }

            $indexingWorkflowItem->indexed_content()->update([
                'preview' => $chunks->first(),
                'indexed_at' => now(),
            ]);
            $indexingWorkflowItem->update([
                'status' => 'prepared',
            ]);
            $this->createEmbedding($indexingWorkflowItem, $embedder, $vectorStore);
            $this->updateWorkflowStatus($indexingWorkflowItem);
        } finally {
            Storage::delete($this->file->path());
        }
    }

    private function indexPDF(PdfParser $pdfParser, TextChunker $textChunker, IndexingWorkflowItem $indexingWorkflowItem)
    {
        $indexingWorkflowItem->update([
            'status' => 'parsing',
        ]);

        $blocks = $pdfParser->stream($this->file->path());
        $firstChunk = '';

        $indexingWorkflowItem->update([
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
                    'indexed_content_id' => $indexingWorkflowItem->indexed_content->id,
                    'body' => $chunk,
                    'position' => $i+1,
                ]);
            }
        }

        $indexingWorkflowItem->indexed_content()->update([
            'preview' => $firstChunk,
            'indexed_at' => now(),
        ]);
        $indexingWorkflowItem->update([
            'status' => 'prepared',
        ]);
    }

    private function createEmbedding(IndexingWorkflowItem $indexingWorkflowItem, Embedder $embedder, VectorStore $vectorStore)
    {
        try {
            $indexingWorkflowItem->update([
                'status' => 'vectorizing',
            ]);

            foreach ($indexingWorkflowItem->indexed_content->chunks as $chunk) {
                $embedding = $embedder->createEmbedding($chunk->getEmbeddableContent());
                $vectorStore->upsert($chunk, $embedding);
            }

            $indexingWorkflowItem->update([
                'status' => 'completed',
            ]);
        } catch (Throwable $e) {
            $indexingWorkflowItem->update([
                'status' => 'warning',
                'error_message' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    private function updateWorkflowStatus(IndexingWorkflowItem $indexingWorkflowItem)
    {
        /** @var IndexingWorkflow $workflow */
        $workflow = $indexingWorkflowItem->indexing_workflow;
        $hasQueuedItems = $workflow->items()
            ->where('status', 'queued')
            ->exists();

        if (!$hasQueuedItems) {
            $workflow->update([
                'status' => 'completed',
            ]);
        }
    }
}
