<?php

namespace App\Services\Search;

use App\Models\DocumentChunk;
use App\Services\Indexing\EntityExtractor;
use App\Services\LLM\Embedder;
use Illuminate\Support\Collection;

class SearchEngine
{
    public function __construct(
        private Embedder $embedder,
        private EntityExtractor $entityExtractor,
    ) {
    }

    public function search(string $question)
    {
        $entities = $this->entityExtractor->extract($question);
        $semanticResults = $this->semanticSearch($question);
        $keywordResults = $this->keywordSearch($entities['keywords']);

        return [
            'semantic' => $semanticResults,
            'keyword' => $keywordResults,
            'entities' => $entities,
        ];
    }

    /**
     * @return Collection<DocumentChunk>
     */
    private function semanticSearch(string $question): Collection
    {
        if (empty($question)) {
            return collect();
        }

        $embedding = $this->embedder->createEmbedding($question);
        $embeddingStr = '[' . implode(',', $embedding) . ']';

        // <=> returns cosine distance
        // (1 - cosine_distance) returns cosine similarity
        return DocumentChunk::query()
            ->selectRaw('*, 1 - (embedding <=> ?) as similarity', [$embeddingStr])
            ->whereRaw('embedding IS NOT NULL')
            ->whereRaw('1 - (embedding <=> ?) > 0.5', [$embeddingStr])
            ->orderByDesc('similarity')
            ->limit(10)
            ->get();
    }

    /**
     * @return Collection<DocumentChunk>
     */
    private function keywordSearch(array $keywords): Collection
    {
        if (empty($keywords)) {
            return collect();
        }

        $query = implode(' | ', $keywords);
        return DocumentChunk::query()
            ->selectRaw("*, ts_rank(search_vector, plainto_tsquery('english', ?)) as rank", [$query])
            ->whereRaw("search_vector @@ plainto_tsquery('english', ?)", [$query])
            ->orderByDesc('rank')
            ->limit(10)
            ->get();
    }
}
