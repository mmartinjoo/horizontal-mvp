<?php

namespace App\Jobs;

use App\Services\KnowledgeGraph\BuildLeidenCommunities;
use App\Services\KnowledgeGraph\KnowledgeGraph;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class IndexLeidenParentCommunities implements ShouldQueue
{
    use Queueable;

    public function __construct()
    {
        //
    }

    public function handle(BuildLeidenCommunities $leidenCommunities): void
    {
        $leidenCommunities->indexParentCommunities();
    }
}
