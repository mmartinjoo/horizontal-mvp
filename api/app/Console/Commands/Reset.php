<?php

namespace App\Console\Commands;

use App\Models\Document;
use App\Models\Participant;
use App\Models\Topic;
use App\Services\GraphDB\GraphDB;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\App;

class Reset extends Command
{
    protected $signature = 'app:reset';

    protected $description = 'Command description';

    public function handle(GraphDB $graphDB)
    {
        if (App::environment('production')) {
            $this->fail('do not run this in production');
        }
        Document::all()->each->delete();
        Participant::all()->each->delete();
        Topic::all()->each->delete();
        $graphDB->query('MATCH (n) DETACH DELETE n');
    }
}
