<?php

namespace App\Http\Controllers;

use App\Models\Question;
use App\Services\Indexing\EntityExtractor;
use App\Services\LLM\Embedder;
use Illuminate\Http\Request;

class QuestionController
{
    public function ask(Request $request, Embedder $embedder, EntityExtractor $entityExtractor)
    {
        $question = Question::create([
            'user_id' => $request->user()->id,
            'question' => $request->input('question'),
        ]);
        $entities = $entityExtractor->extract($request->input('question'));
        $question->update(['entities' => $entities]);
        return $entities;
//        $embedding = $embedder->createEmbedding($request->input('question'));
//        $embeddingStr = '[' . implode(',', $embedding) . ']';
//
//        // <=> returns cosine distance
//        // (1 - cosine_distance) returns cosine similarity
//        $chunks = DocumentChunk::selectRaw('
//              *,
//              1 - (embedding <=> ?) as similarity
//          ', [$embeddingStr])
//            ->whereRaw('embedding IS NOT NULL')
//            ->whereRaw('1 - (embedding <=> ?) > 0.5', [$embeddingStr])
//            ->orderByDesc('similarity')
//            ->limit(10)
//            ->get();

//        $chunks = IndexedContentChunk::query()
//             ->whereRaw('search_vector @@ plainto_tsquery(?)', [$request->input('question')])
//              ->orderByRaw('ts_rank(search_vector, plainto_tsquery(?)) DESC', [$request->input('question')])
//            ->limit(10)
//            ->get();
    }
}
