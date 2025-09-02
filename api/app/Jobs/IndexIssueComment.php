<?php

namespace App\Jobs;

use App\Exceptions\EmbeddingException;
use App\Models\DocumentComment;
use App\Services\GraphDB\GraphDB;
use App\Services\Indexing\EntityExtractor;
use App\Services\LLM\Embedder;
use App\Services\VectorStore\VectorStore;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class IndexIssueComment implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private DocumentComment $comment,
    ) {
    }

    public function handle(
        Embedder $embedder,
        VectorStore $vectorStore,
        EntityExtractor $entityExtractor,
        GraphDB $graphDB,
    ): void {
        try {
            $embedding = $embedder->createEmbedding($this->comment->getEmbeddableContent());
            $vectorStore->upsert($this->comment, $embedding);
            $graphDB->createNodeWithRelation(
                newNodeLabel: 'IssueComment',
                newNodeAttributes: [
                    'id' => $this->comment->id,
                    'embedding' => $embedding,
                ],
                relation: 'COMMENT_OF',
                relatedNodeLabel: 'Issue',
                relatedNodeID: $this->comment->document->id,
            );

            if ($this->comment->author) {
                $graphDB->createNodeWithRelation(
                    newNodeLabel: 'Participant',
                    newNodeAttributes: [
                        'id' => $this->comment->author->id,
                        'name' => $this->comment->author->name,
                    ],
                    relation: 'AUTHOR_OF',
                    relatedNodeLabel: 'IssueComment',
                    relatedNodeID: $this->comment->id,
                );
            }

            $participants = $entityExtractor->extractParticipants($this->comment->body);
            $this->comment->createParticipants($participants);
            foreach ($this->comment->participants as $participant) {
                $graphDB->createNodeWithRelation(
                    newNodeLabel: 'Participant',
                    newNodeAttributes: [
                        'id' => $participant->id,
                        'name' => $participant->name,
                    ],
                    relation: 'MENTIONED_IN',
                    relatedNodeLabel: 'IssueComment',
                    relatedNodeID: $this->comment->id,
                );
            }

            $topics = $entityExtractor->extractTopics($this->comment->body);
            $this->comment->createTopics($topics['topics']);
            foreach ($this->comment->topics as $topic) {
                $graphDB->createNodeWithRelation(
                    newNodeLabel: 'Topic',
                    newNodeAttributes: [
                        'id' => $topic->id,
                        'name' => $topic->name,
                    ],
                    relation: 'MENTIONED_IN',
                    relatedNodeLabel: 'IssueComment',
                    relatedNodeID: $this->comment->id,
                );
            }
        } catch (Throwable $e) {
            throw EmbeddingException::wrap($e);
        }
    }
}
