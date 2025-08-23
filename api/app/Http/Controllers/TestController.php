<?php

namespace App\Http\Controllers;

use App\Integrations\Storage\GoogleDrive;
use App\Jobs\SyncGoogleDrive;
use App\Models\User;
use App\Services\Indexing\Synchronizer;
use Illuminate\Http\Request;

class TestController extends Controller
{
    public function index(Request $request, GoogleDrive $drive, Synchronizer $synchronizer)
    {
        $user = User::first();
        SyncGoogleDrive::dispatch($user);
//        $contents = $drive->listDirectoryContents('deQenQ');
//        $synchronizer->syncStorage($contents);

        return response('syncing ');
    }
}
