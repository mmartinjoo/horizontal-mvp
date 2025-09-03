<?php

namespace App\Console\Commands;

use App\Models\Document;
use App\Models\Participant;
use App\Models\Topic;
use App\Services\GraphDB\GraphDB;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;

class Reset extends Command
{
    protected $signature = 'reset';

    protected $description = 'Command description';

    public function handle(GraphDB $graphDB)
    {
        $sure = $this->confirm('Are you sure?', true);
        if (!$sure) {
            return;
        }
        if (App::environment('production')) {
            $this->fail('do not run this in production');
        }
        Document::all()->each->delete();
        Participant::all()->each->delete();
        Topic::all()->each->delete();
        DB::table('jobs')->delete();
        DB::table('failed_jobs')->delete();
        $graphDB->query('MATCH (n) DETACH DELETE n');
    }
}
