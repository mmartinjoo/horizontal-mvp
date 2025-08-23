<?php

namespace App\Http\Controllers;

use App\Jobs\IndexGoogleDrive;
use App\Models\User;

class TestController extends Controller
{
    public function index()
    {
        $user = User::first();
        IndexGoogleDrive::dispatch($user);

        return response('syncing ');
    }
}
