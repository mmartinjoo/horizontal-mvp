<?php

namespace App\Services\Search;

use App\Models\DocumentChunk;
use App\Services\Indexing\EntityExtractor;
use App\Services\LLM\Embedder;
use App\Services\Search\DataTransferObjects\SearchResult;
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
        /** @var Collection<SearchResult> $combined */
        $combined = $this->combineResults($semanticResults, $keywordResults);

        return [
            'combined' => $this->rankResults($combined, $entities),
            'semantic' => $semanticResults,
            'keyword' => $keywordResults,
            'entities' => $entities,
        ];
    }

    /**
     * @param Collection<SearchResult> $results
     * @return Collection<SearchResult>
     */
    private function rankResults(Collection $results, array $entities)
    {
        foreach ($results as $result) {
            $score = 0;
            if ($result->semanticScore) {
                $score += $result->semanticScore * 0.75;
            }
            if ($result->keywordScore) {
                $score += $result->keywordScore * 0.5;
            }
            $result->weightedScore = max($score, 1);
        }
        return $results->sortByDesc('score');
    }

    /**
     * @return Collection<SearchResult>
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
        $chunks = DocumentChunk::query()
            ->selectRaw('*, 1 - (embedding <=> ?) as semantic_score', [$embeddingStr])
            ->whereRaw('embedding IS NOT NULL')
            ->whereRaw('1 - (embedding <=> ?) > 0.5', [$embeddingStr])
            ->orderByDesc('semantic_score')
            ->limit(10)
            ->get();

        return $chunks->map(fn (DocumentChunk $chunk) => new SearchResult(
            documentChunk: $chunk,
            semanticScore: $chunk->semantic_score,
            keywordScore: null,
        ));
    }

    /**
     * @return Collection<SearchResult>
     */
    private function keywordSearch(array $keywords): Collection
    {
        if (empty($keywords)) {
            return collect();
        }

        $query = implode(' | ', $keywords);
        $chunks = DocumentChunk::query()
            ->selectRaw("*, ts_rank(search_vector, plainto_tsquery('english', ?)) as keyword_score", [$query])
            ->whereRaw("search_vector @@ plainto_tsquery('english', ?)", [$query])
            ->orderByDesc('keyword_score')
            ->limit(10)
            ->get();

        return $chunks->map(fn (DocumentChunk $chunk) => new SearchResult(
            documentChunk: $chunk,
            semanticScore: null,
            keywordScore: $chunk->keyword_score,
        ));
    }

    /**
     * @param Collection<SearchResult> $semanticResults
     * @param Collection<SearchResult> $keywordResults
     * @return Collection<SearchResult>
     */
    private function combineResults(Collection $semanticResults, Collection $keywordResults): Collection
    {
        $results = collect();
        foreach ($semanticResults as $result) {
            $results->push(new SearchResult(
                documentChunk: $result->documentChunk,
                semanticScore: $result->semanticScore,
                keywordScore: null,
            ));
        }
        foreach ($keywordResults as $result) {
            /** @var SearchResult $existingItem */
            $existingItem = $results
                ->where(fn (SearchResult $searchRes) =>
                    $searchRes->documentChunk->id === $result->documentChunk->id
                )
                ->first();

            if ($existingItem) {
                $existingItem->keywordScore = $result->keywordScore;
            } else {
                $results->push(new SearchResult(
                    documentChunk: $result->documentChunk,
                    semanticScore: null,
                    keywordScore: $result->keywordScore,
                ));
            }
        }
        return $results;
    }
}
