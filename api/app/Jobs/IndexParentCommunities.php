<?php

namespace App\Jobs;

use App\Services\KnowledgeGraph\KnowledgeGraph;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class IndexParentCommunities implements ShouldQueue
{
    use Queueable;

    public function __construct()
    {
        //
    }

    public function handle(KnowledgeGraph $knowledgeGraph): void
    {
        $knowledgeGraph->indexParentCommunities();
    }
}
