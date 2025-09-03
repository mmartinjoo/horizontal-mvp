<?php

namespace App\Jobs;

use App\Exceptions\EmbeddingException;
use App\Models\DocumentWorklog;
use App\Services\GraphDB\GraphDB;
use App\Services\Indexing\EntityExtractor;
use App\Services\LLM\Embedder;
use App\Services\VectorStore\VectorStore;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class IndexIssueWorklog implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private DocumentWorklog $worklog,
    ) {
    }

    public function handle(
        Embedder $embedder,
        VectorStore $vectorStore,
        EntityExtractor $entityExtractor,
        GraphDB $graphDB,
    ): void {
        try {
            $embedding = $embedder->createEmbedding($this->worklog->getEmbeddableContent());
            $vectorStore->upsert($this->worklog, $embedding);
            $graphDB->createNodeWithRelation(
                newNodeLabel: 'IssueWorklog',
                newNodeAttributes: [
                    'id' => $this->worklog->id,
                    'description' => $this->worklog->description,
                    'embedding' => $embedding,
                ],
                relation: 'WORKLOG_FOR',
                relatedNodeLabel: 'Issue',
                relatedNodeID: $this->worklog->document->id,
            );

            if ($this->worklog->author) {
                $graphDB->createNodeWithRelation(
                    newNodeLabel: 'Participant',
                    newNodeAttributes: [
                        'id' => $this->worklog->author->id,
                        'name' => $this->worklog->author->name,
                        'embedding' => $this->worklog->author->embedding,
                    ],
                    relation: 'AUTHOR_OF',
                    relatedNodeLabel: 'IssueWorklog',
                    relatedNodeID: $this->worklog->id,
                );
            }

            if (!$this->worklog->comment) {
                return;
            }

            $participants = $entityExtractor->extractParticipants($this->worklog->comment);
            $this->worklog->createParticipants($participants);
            foreach ($this->worklog->participants as $participant) {
                $graphDB->createNodeWithRelation(
                    newNodeLabel: 'Participant',
                    newNodeAttributes: [
                        'id' => $participant->id,
                        'name' => $participant->name,
                        'embedding' => $participant->embedding,
                    ],
                    relation: 'MENTIONED_IN',
                    relatedNodeLabel: 'IssueWorklog',
                    relatedNodeID: $this->worklog->id,
                    relationAttributes: [
                        'context' => $participant->pivot->context,
                        'embedding' => $participant->pivot->embedding,
                    ],
                );
            }

            $topics = $entityExtractor->extractTopics($this->worklog->comment);
            $this->worklog->createTopics($topics['topics']);
            foreach ($this->worklog->topics as $topic) {
                $graphDB->createNodeWithRelation(
                    newNodeLabel: 'Topic',
                    newNodeAttributes: [
                        'id' => $topic->id,
                        'name' => $topic->name,
                        'embedding' => $topic->embedding,
                    ],
                    relation: 'MENTIONED_IN',
                    relatedNodeLabel: 'IssueWorklog',
                    relatedNodeID: $this->worklog->id,
                    relationAttributes: [
                        'context' => $topic->pivot->context,
                        'embedding' => $topic->pivot->embedding,
                    ],
                );
            }
        } catch (Throwable $e) {
            throw EmbeddingException::wrap($e);
        }
    }
}
