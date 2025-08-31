<?php

namespace App\Jobs;

use App\Exceptions\EmbeddingException;
use App\Integrations\Communication\Issue;
use App\Models\DocumentComment;
use App\Services\Indexing\EntityExtractor;
use App\Services\LLM\Embedder;
use App\Services\VectorStore\VectorStore;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Arr;
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
    ): void {
        try {
            $embedding = $embedder->createEmbedding($this->comment->getEmbeddableContent());
            $vectorStore->upsert($this->comment, $embedding);

            $participants = $entityExtractor->extractParticipants($this->comment->body);
            if ($this->comment->author) {
                $participants['people'][] = [
                    'name' => $this->issue->assignee,
                    'context' => 'assignee',
                ];
            }
            $this->comment->createParticipants($participants);

            $topics = $entityExtractor->extractTopics($this->comment->body);
            $this->comment->createTopics($topics['topics']);
        } catch (Throwable $e) {
            throw EmbeddingException::wrap($e);
        }
    }
}
