<?php

namespace App\Jobs;

use App\Integrations\Communication\Jira\Jira;
use App\Models\IndexedContent;
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

            $prios = ['high', 'medium', 'low'];
            foreach ($prios as $prio) {
                $dateRange = match($prio) {
                    'high' => ['from' => now()->subMonths(1), 'to' => now()],
                    'medium' => ['from' => now()->subMonths(3), 'to' => now()->subMonths(1)],
                    'low' => ['from' => now()->subMonths(6), 'to' => now()->subMonths(3)],
                };
                $issues = $jira->getIssues($this->team, $project['key'], $dateRange['from'], $dateRange['to']);
                foreach ($issues as $issue) {
                    IndexedContent::create([
                        'team_id' => $this->team->id,
                        'user_id' => 1,
                        'source_type' => 'jira',
                        'source_id' => $issue['id'],
                        'source_url' => $issue['self'],
                        'title' => $issue['key'],
                        'priority' => $prio,
                        'metadata' => $issue,
                    ]);
                }
            }
        }
    }
}
