<?php

namespace App\Http\Controllers;

use App\Jobs\SyncGoogleDrive;
use App\Models\User;

class TestController extends Controller
{
    public function index()
    {
        $user = User::first();
        SyncGoogleDrive::dispatch($user);

        return response('syncing ');
    }
}
