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
        $questionEntities = $this->entityExtractor->extract($question);
        $semanticResults = $this->semanticSearch($question);
        $keywordResults = $this->keywordSearch($questionEntities['keywords']);
        /** @var Collection<SearchResult> $combined */
        $combined = $this->combineResults($semanticResults, $keywordResults);

        return [
            'combined' => $this->rankResults($combined, $questionEntities),
            'semantic' => $semanticResults,
            'keyword' => $keywordResults,
            'entities' => $questionEntities,
        ];
    }

    /**
     * @param Collection<SearchResult> $results
     * @return Collection<SearchResult>
     */
    private function rankResults(Collection $results, array $questionEntities)
    {
        foreach ($results as $result) {
            $score = 0;
            if ($result->semanticScore) {
                $score += $result->semanticScore * 0.75;
            }
            if ($result->keywordScore) {
                $score += $result->keywordScore * 0.5;
            }
            $result->weightedScore = min($score, 1);

            $people = $result->documentChunk
                ->entities
                ->people;

            foreach ($people as $person) {
                if (in_array($person, $questionEntities['people'])) {
                    $result->weightedScore = min($result->weightedScore * 1.25, 1);
                    $result->peopleBonus = 1.25;
                    break;
                }
            }
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
