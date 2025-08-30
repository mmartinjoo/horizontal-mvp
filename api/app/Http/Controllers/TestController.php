<?php

namespace App\Http\Controllers;

use App\Integrations\Communication\Jira\JiraTokenManager;
use App\Jobs\IndexGoogleDrive;
use App\Jobs\IndexJira;
use App\Models\DocumentChunk;
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
        $chunks = DocumentChunk::whereRaw(
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
}
