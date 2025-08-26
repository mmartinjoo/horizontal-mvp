<?php

namespace App\Jobs;

use App\Integrations\Communication\Jira\Jira;
use App\Models\JiraIntegration;
use App\Models\JiraProject;
use App\Models\Team;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class IndexJira implements ShouldQueue
{
    use Queueable;

    public function __construct(private Team $team)
    {
    }

    public function handle(Jira $jira): void
    {
        $jiraIntegration = JiraIntegration::where('team_id', $this->team->id)->firstOrFail();
        $projects = $jira->getProjects($this->team);
        foreach ($projects as $project) {
            JiraProject::create([
                'team_id' => $this->team->id,
                'jira_integration_id' => $jiraIntegration->id,
                'title' => $project['name'],
                'key' => $project['key'],
                'jira_id' => $project['id'],
            ]);
        }
    }
}
