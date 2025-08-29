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

            $entities = $entityExtractor->extract($this->comment->body);
            $people = $entities['people'];
            if ($this->comment->author && !in_array($this->comment->author, $entities['people'])) {
                $people[] = $this->comment->author;
            }
            $this->comment->entities()->create([
                'keywords' => $entities['keywords'],
                'people' => $people,
                'dates' => $entities['dates'],
            ]);
        } catch (Throwable $e) {
            throw EmbeddingException::wrap($e);
        }
    }
}
