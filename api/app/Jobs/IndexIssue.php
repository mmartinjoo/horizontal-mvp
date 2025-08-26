<?php

namespace App\Jobs;

use App\Exceptions\EmbeddingException;
use App\Exceptions\NoContentToIndexException;
use App\Integrations\Communication\Issue;
use App\Models\IndexedContentChunk;
use App\Models\IndexingWorkflow;
use App\Models\IndexingWorkflowItem;
use App\Services\Indexing\TextChunker;
use App\Services\LLM\Embedder;
use App\Services\VectorStore\VectorStore;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class IndexIssue implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private Issue $issue,
        private int $indexingWorkflowItemId,
    ) {
    }

    public function handle(TextChunker $textChunker, Embedder $embedder, VectorStore $vectorStore): void
    {
        $indexingWorkflowItem = IndexingWorkflowItem::findOrFail($this->indexingWorkflowItemId);
        $chunks = $textChunker->chunk($this->issue->toString());
        if (count($chunks) === 0) {
            $indexingWorkflowItem->update([
                'status' => 'warning',
            ]);
            throw new NoContentToIndexException('Chunk is empty: ' . json_encode($this->issue) . '; content: ' . $this->issue->toString());
        }
        if (count($chunks) === 1 && strlen(trim($chunks->first())) === 0) {
            $indexingWorkflowItem->update([
                'status' => 'warning',
            ]);
            throw new NoContentToIndexException('Chunk contains one empty item: ' . json_encode($this->issue) . '; content: ' . $this->issue->toString());
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
            throw EmbeddingException::wrap($e);
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
