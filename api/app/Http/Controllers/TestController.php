<?php

namespace App\Http\Controllers;

use App\Integrations\Communication\Jira\JiraTokenManager;
use App\Jobs\IndexGoogleDrive;
use App\Models\User;

class TestController extends Controller
{
    public function index()
    {
        $user = User::first();
        IndexGoogleDrive::dispatch($user);
//
        return response('syncing ');
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

    public function jira()
    {
        // Example of using JiraApiClient
        $team = \App\Models\Team::where('name', 'Test Company')->first();

        if (!$team) {
            return response()->json(['error' => 'No team found'], 404);
        }

        try {
            $jiraClient = app(\App\Integrations\Communication\Jira\Jira::class);

            // Get current user info
            $user = $jiraClient->getCurrentUser($team);

            // Get projects
            $projects = $jiraClient->getProjects($team);

            return response()->json([
                'user' => $user,
                'projects' => $projects,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function refresh(JiraTokenManager $jiraTokenManager)
    {
        return $jiraTokenManager->refreshTokensExpiringSoon(30);
    }
}
