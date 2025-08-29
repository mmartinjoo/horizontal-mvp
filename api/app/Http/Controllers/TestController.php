<?php

namespace App\Http\Controllers;

use App\Integrations\Communication\Jira\JiraTokenManager;
use App\Jobs\IndexGoogleDrive;
use App\Jobs\IndexJira;
use App\Models\IndexedContentChunk;
use App\Models\Team;
use App\Services\LLM\Embedder;
use Illuminate\Http\Request;

class TestController extends Controller
{
    public function index()
    {
        $team = Team::where('name', 'Test Company')->firstOrFail();
        IndexJira::dispatch($team);
        IndexGoogleDrive::dispatch($team);

        return response('indexing...');
    }

    public function search()
    {
        $chunks = IndexedContentChunk::whereRaw(
            "search_vector @@ plainto_tsquery('english', ?)",
            ['landing page']
        )->get();

        dd($chunks);
    }

    public function token()
    {
        $team = \App\Models\Team::where(['name' => 'Test Company'])->firstOrFail();

        $user = \App\Models\User::create([
            'name' => 'Test User',
            'email' => 'jira1@example.com',
            'password' => bcrypt('password'),
            'team_id' => $team->id
        ]);

        $token = $user->createToken('test-token')->plainTextToken;
        dd($token);
    }

    public function refresh(JiraTokenManager $jiraTokenManager)
    {
        return $jiraTokenManager->refreshTokensExpiringSoon(30);
    }

    public function ask(Request $request, Embedder $embedder)
    {
        $embedding = $embedder->createEmbedding($request->input('question'));
        $embeddingStr = '[' . implode(',', $embedding) . ']';

        // <=> returns cosine distance
        // (1 - cosine_distance) returns cosine similarity
        $chunks = IndexedContentChunk::selectRaw('
              *,
              1 - (embedding <=> ?) as similarity
          ', [$embeddingStr])
        ->whereRaw('embedding IS NOT NULL')
        ->whereRaw('1 - (embedding <=> ?) > 0.5', [$embeddingStr])
        ->orderByDesc('similarity')
        ->limit(10)
        ->get();

//        $chunks = IndexedContentChunk::query()
//             ->whereRaw('search_vector @@ plainto_tsquery(?)', [$request->input('question')])
//              ->orderByRaw('ts_rank(search_vector, plainto_tsquery(?)) DESC', [$request->input('question')])
//            ->limit(10)
//            ->get();

        dd($chunks);
    }
}
