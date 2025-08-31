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
            $keys = ['people', 'organizations'];
            foreach ($keys as $key) {
                foreach ($participants[$key] as $person) {
                    if (Arr::get($person, 'confidence') === 'low') {
                        continue;
                    }
                    if (!Arr::get($person, 'name')) {
                        continue;
                    }
                    $this->comment->participants()->create([
                        'name' => $person['name'],
                        'context' => Arr::get($person, 'context'),
                    ]);
                }
                $topics = $entityExtractor->extractTopics($this->comment->body);
                foreach ($topics['topics'] as $topic) {
                    if (!Arr::get($topic, 'name')) {
                        continue;
                    }
                    if (Arr::get($topic, 'importance', 'low') === 'low') {
                        continue;
                    }
                    $this->comment->topics()->create([
                        'name' => $topic['name'],
                        'variations' => $topic['variations'],
                        'category' => $topic['category'],
                    ]);
                }
            }
        } catch (Throwable $e) {
            throw EmbeddingException::wrap($e);
        }
    }
}
