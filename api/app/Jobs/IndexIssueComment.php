<?php

namespace App\Jobs;

use App\Exceptions\EmbeddingException;
use App\Integrations\Communication\Issue;
use App\Models\DocumentComment;
use App\Services\GraphDB\GraphDB;
use App\Services\Indexing\EntityExtractor;
use App\Services\LLM\Embedder;
use App\Services\VectorStore\VectorStore;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Throwable;

class IndexIssueComment implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private Issue           $issue,
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
                relatedNodeID: $this->issue->id,
            );

            $participants = $entityExtractor->extractParticipants($this->comment->body);
            if ($this->comment->author) {
                $count = collect($participants['people'])
                    ->filter(fn (array $person) => Str::slug($person['name']) === Str::slug($this->comment->author))
                    ->count();
                if ($count === 0) {
                    $participants['people'][] = [
                        'name' => $this->issue->assignee,
                        'context' => 'assignee',
                    ];
                }
            }
            $this->comment->createParticipants($participants);
            foreach ($this->comment->participants as $participant) {
                $graphDB->createNodeWithRelation(
                    newNodeLabel: 'Participant',
                    newNodeAttributes: [
                        'id' => $participant->id,
                        'name' => $participant->name,
                        'slug' => $participant->slug,
                    ],
                    relation: $participant->slug === Str::slug($this->comment->author) ? 'AUTHOR_OF' : 'PARTICIPATED_IN',
                    relatedNodeLabel: 'Issue',
                    relatedNodeID: $this->issue->id,
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
                        'slug' => $topic->slug,
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
