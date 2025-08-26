<?php

namespace App\Jobs;

use App\Integrations\Communication\Issue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class IndexIssue implements ShouldQueue
{
    use Queueable;

    public function __construct(private Issue $issue)
    {
    }

    public function handle(): void
    {
    }
}
