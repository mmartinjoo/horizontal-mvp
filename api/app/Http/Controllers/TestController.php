<?php

namespace App\Http\Controllers;

use App\Integrations\Storage\GoogleDrive;
use App\Services\Indexing\Synchronizer;
use Illuminate\Http\Request;

class TestController extends Controller
{
    public function index(Request $request, GoogleDrive $drive, Synchronizer $synchronizer)
    {
        $contents = $drive->listDirectoryContents('deQenQ');
        $synchronizer->syncStorage($contents);

        return response('syncing ' . count($contents['high']) . ' files');
    }
}
