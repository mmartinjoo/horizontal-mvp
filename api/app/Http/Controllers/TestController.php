<?php

namespace App\Http\Controllers;

use App\Jobs\IndexGoogleDrive;
use App\Models\IndexedContent;
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

    public function jira()
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
}
