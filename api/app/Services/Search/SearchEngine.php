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
        $combined = $this->combineResults($semanticResults, $keywordResults);

        return [
            'combined' => $this->rankResults($combined, $entities),
            'semantic' => $semanticResults,
            'keyword' => $keywordResults,
            'entities' => $entities,
        ];
    }

    private function rankResults(Collection $results, array $entities)
    {
        foreach ($results as $result) {
            $score = 0;
            if ($result->semantic_score) {
                $score += $result->semantic_score * 0.5;
            }
            if ($result->keyword_score) {
                $score += $result->keyword_score * 0.25;
            }
            $result->score = $score;
        }
        return $results->sortByDesc('score');
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
            ->selectRaw('*, 1 - (embedding <=> ?) as semantic_score', [$embeddingStr])
            ->whereRaw('embedding IS NOT NULL')
            ->whereRaw('1 - (embedding <=> ?) > 0.5', [$embeddingStr])
            ->orderByDesc('semantic_score')
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
            ->selectRaw("*, ts_rank(search_vector, plainto_tsquery('english', ?)) as keyword_score", [$query])
            ->whereRaw("search_vector @@ plainto_tsquery('english', ?)", [$query])
            ->orderByDesc('keyword_score')
            ->limit(10)
            ->get();
    }

    /**
     * @return Collection<DocumentChunk>
     */
    private function combineResults(Collection $semanticResults, Collection $keywordResults): Collection
    {
        $results = collect();
        foreach ($semanticResults as $result) {
            $result->keyword_score = null;
            $results->push($result);
        }
        foreach ($keywordResults as $result) {
            $existingItem = $results->firstWhere('id', $result->id);
            if ($existingItem) {
                $existingItem->keyword_score = $result->keyword_score;
            } else {
                $results->push($result);
            }
        }
        return $results;
    }
}
