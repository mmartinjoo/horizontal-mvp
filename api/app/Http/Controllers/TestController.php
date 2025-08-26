<?php

namespace App\Http\Controllers;

use App\Integrations\Communication\Jira\JiraTokenManager;
use App\Jobs\IndexGoogleDrive;
use App\Jobs\IndexJira;
use App\Models\User;

class TestController extends Controller
{
    public function index()
    {
        $user = User::where('email', 'jira@example.com')->firstOrFail();
        IndexJira::dispatch($user->team);
//        IndexGoogleDrive::dispatch($user);

        return response('indexing...');
    }

    public function token()
    {
        $team = \App\Models\Team::create(['name' => 'Test Company']);

        $user = \App\Models\User::create([
            'name' => 'Test User',
            'email' => 'jira@example.com',
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
