<?php

namespace App\Jobs;

use App\Exceptions\EmbeddingException;
use App\Exceptions\NoContentToIndexException;
use App\Integrations\Communication\Issue;
use App\Models\DocumentChunk;
use App\Models\IndexingWorkflow;
use App\Models\IndexingWorkflowItem;
use App\Models\Participant;
use App\Services\GraphDB\GraphDB;
use App\Services\Indexing\EntityExtractor;
use App\Services\Indexing\TextChunker;
use App\Services\LLM\Embedder;
use App\Services\VectorStore\VectorStore;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Throwable;

class IndexIssue implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private Issue $issue,
        private int $indexingWorkflowItemId,
    ) {
    }

    public function handle(
        TextChunker $textChunker,
        Embedder $embedder,
        VectorStore $vectorStore,
        EntityExtractor $entityExtractor,
        GraphDB $graphDB,
    ): void {
        $indexingWorkflowItem = IndexingWorkflowItem::findOrFail($this->indexingWorkflowItemId);
        $chunks = $textChunker->chunk($this->issue->title . ' ' . $this->issue->description);
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
            DocumentChunk::create([
                'document_id' => $indexingWorkflowItem->document->id,
                'body' => $chunk,
                'position' => $i+1,
            ]);
        }
        $indexingWorkflowItem->document()->update([
            'preview' => $chunks->first(),
            'indexed_at' => now(),
        ]);
        $indexingWorkflowItem->update([
            'status' => 'prepared',
        ]);
        $this->createEmbedding($indexingWorkflowItem, $embedder, $vectorStore, $graphDB);
        $this->createEntities($indexingWorkflowItem, $entityExtractor, $graphDB, $embedder);
        $this->updateWorkflowStatus($indexingWorkflowItem);
    }

    private function createEmbedding(
        IndexingWorkflowItem $indexingWorkflowItem,
        Embedder $embedder,
        VectorStore $vectorStore,
        GraphDB $graphDB,
    ) {
        try {
            $indexingWorkflowItem->update([
                'status' => 'vectorizing',
            ]);

            foreach ($indexingWorkflowItem->document->chunks as $chunk) {
                $embedding = $embedder->createEmbedding($chunk->getEmbeddableContent());
                $vectorStore->upsert($chunk, $embedding);
                $graphDB->createNodeWithRelation(
                    newNodeLabel: 'IssueChunk',
                    newNodeAttributes: [
                        'id' => $chunk->id,
                        'embedding' => $embedding,
                    ],
                    relation: 'CHUNK_OF',
                    relatedNodeLabel: 'Issue',
                    relatedNodeID: $indexingWorkflowItem->document->id,
                );
            }

            $indexingWorkflowItem->update([
                'status' => 'vectorizing_completed',
            ]);
        } catch (Throwable $e) {
            $indexingWorkflowItem->update([
                'status' => 'warning',
                'error_message' => $e->getMessage(),
            ]);
            throw EmbeddingException::wrap($e);
        }
    }

    private function createEntities(
        IndexingWorkflowItem $indexingWorkflowItem,
        EntityExtractor $entityExtractor,
        GraphDB $graphDB,
        Embedder $embedder,
    ) {
        try {
            $indexingWorkflowItem->update([
                'status' => 'extracting_entities',
            ]);

            if ($this->issue->assignee) {
                $assignee = Participant::updateOrCreate(
                    [
                        'slug' => Str::slug($this->issue->assignee),
                        'type' => 'person',
                    ],
                    [
                        'slug' => Str::slug($this->issue->assignee),
                        'name' => $this->issue->assignee,
                        'type' => 'person',
                        'embedding' => $embedder->createEmbedding($this->issue->assignee),
                    ],
                );
                $graphDB->createNodeWithRelation(
                    newNodeLabel: 'Participant',
                    newNodeAttributes: [
                        'id' => $assignee->id,
                        'name' => $assignee->name,
                        'embedding' => $assignee->embedding,
                    ],
                    relation: 'ASSIGNEE_OF',
                    relatedNodeLabel: 'Issue',
                    relatedNodeID: $indexingWorkflowItem->document->id,
                );
            }

            /** @var DocumentChunk $chunk */
            foreach ($indexingWorkflowItem->document->chunks as $chunk) {
                $participants = $entityExtractor->extractParticipants($chunk->body);
                $chunk->createParticipants($participants);
                foreach ($chunk->participants as $participant) {
                    $graphDB->createNodeWithRelation(
                        newNodeLabel: 'Participant',
                        newNodeAttributes: [
                            'id' => $participant->id,
                            'name' => $participant->name,
                            'embedding' => $participant->embedding,
                        ],
                        relation: $participant->context === 'assignee' ? 'ASSIGNEE_OF' : 'PARTICIPATED_IN',
                        relatedNodeLabel: 'Issue',
                        relatedNodeID: $chunk->document->id,
                        relationAttributes: [
                            'context' => $participant->pivot->context,
                            'embedding' => $participant->pivot->embedding,
                        ],
                    );
                }

                $topics = $entityExtractor->extractTopics($chunk->body);
                $chunk->createTopics($topics['topics']);
                foreach ($chunk->topics as $topic) {
                    $graphDB->createNodeWithRelation(
                        newNodeLabel: 'Topic',
                        newNodeAttributes: [
                            'id' => $topic->id,
                            'name' => $topic->name,
                            'embedding' => $topic->embedding,
                        ],
                        relation: 'MENTIONED_IN',
                        relatedNodeLabel: 'IssueChunk',
                        relatedNodeID: $chunk->id,
                        relationAttributes: [
                            'context' => $topic->pivot->context,
                            'embedding' => $topic->pivot->embedding,
                        ],
                    );
                }
            }

            $indexingWorkflowItem->update([
                'status' => 'extracting_entities_completed',
            ]);
        } catch (Throwable $e) {
            $indexingWorkflowItem->update([
                'status' => 'warning',
                'error_message' => $e->getMessage(),
            ]);
            logger($e);
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
