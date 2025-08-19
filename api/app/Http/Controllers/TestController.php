<?php

namespace App\Http\Controllers;

use App\Jobs\CreateEmbeddingJob;
use App\Models\Content;
use App\Services\LLM\Embedder;
use App\Services\VectorStore\VectorStore;
use Illuminate\Http\Request;

class TestController extends Controller
{
    public function ask(Request $request, Embedder $embedder)
    {
        $questionEmbedding = $embedder->createEmbedding($request->input('question'));
        $contents = Content::selectRaw('*, (embedding <=> ?) as distance', [
                json_encode($questionEmbedding),
            ])
            ->whereRaw('embedding <=> ? < ?', [
                json_encode($questionEmbedding),
                0.3,
            ])
            ->orderBy('distance')
            ->limit(10)
            ->get();

        return $contents;
    }

    public function index(Request $request, VectorStore $vectorStore)
    {
        $content = Content::find($request->input('content_id'));;
        CreateEmbeddingJob::dispatch($content, $vectorStore);
    }
}
