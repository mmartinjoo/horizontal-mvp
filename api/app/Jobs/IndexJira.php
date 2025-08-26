<?php

namespace App\Jobs;

use App\Integrations\Communication\Issue;
use App\Integrations\Communication\IssueComment;
use App\Integrations\Communication\Jira\Jira;
use App\Models\IndexedContent;
use App\Models\IndexedContentComment;
use App\Models\IndexingWorkflow;
use App\Models\IndexingWorkflowItem;
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
        /** @var IndexingWorkflow $indexing */
        $indexing = IndexingWorkflow::create([
            'integration' => 'jira',
            'status' => 'syncing',
            'user_id' => $this->team->users->first()->id,
            'job_id' => $this->job->payload()['uuid'],
        ]);

        $jiraIntegration = JiraIntegration::where('team_id', $this->team->id)->firstOrFail();
        $projects = $jira->getProjects($this->team);
        foreach ($projects as $project) {
            $exists = JiraProject::query()
                ->where('key', $project['key'])
                ->where('team_id', $this->team->id)
                ->exists();

            if (!$exists) {
                JiraProject::create([
                    'team_id' => $this->team->id,
                    'jira_integration_id' => $jiraIntegration->id,
                    'title' => $project['name'],
                    'key' => $project['key'],
                    'jira_id' => $project['id'],
                ]);
            }

            $prios = ['high', 'medium', 'low'];
            foreach ($prios as $prio) {
                $dateRange = match($prio) {
                    'high' => ['from' => now()->subMonths(1), 'to' => now()],
                    'medium' => ['from' => now()->subMonths(3), 'to' => now()->subMonths(1)],
                    'low' => ['from' => now()->subMonths(6), 'to' => now()->subMonths(3)],
                };
                $issues = $jira->getIssues($this->team, $project['key'], $dateRange['from'], $dateRange['to']);
                foreach ($issues as $i => $issueData) {
                    $description = $this->extractTextFromDocument($issueData['fields']['description']);
                    $issue = Issue::fromJira($issueData, $description);
                    if (!$this->issueNeedsIndexing($issue)) {
                        $indexing->increment('skipped_items', 1);
                        if ($i === count($issues) - 1) {
                            $indexing->update([
                                'status' => 'completed',
                            ]);
                        }
                        continue;
                    }
                    $count = IndexedContent::where('source_id', $issue->id)->delete();
                    $indexing->increment('deleted_items', $count);
                    $content = IndexedContent::create([
                        'team_id' => $this->team->id,
                        'user_id' => 1,
                        'source_type' => 'jira',
                        'source_id' => $issue->id,
                        'source_url' => $issue->url,
                        'title' => $issue->title,
                        'priority' => $prio,
                        'metadata' => $issueData,
                    ]);
                    $indexingItem = IndexingWorkflowItem::create([
                        'indexing_workflow_id' => $indexing->id,
                        'data' => $issue,
                        'status' => 'queued',
                        'indexed_content_id' => $content->id,
                    ]);
                    IndexIssue::dispatch($issue, $indexingItem->id);

                    $commentsData = $jira->getIssueComments($this->team, $issue);
                    $bodies = [];
                    foreach ($commentsData as $commentData) {
                        $bodies[] = $this->extractTextFromDocument($commentData['body']);
                    }
                    $comments = IssueComment::collectJira($commentsData, $bodies);
                    foreach ($comments as $comment) {
                        $indexedComment = IndexedContentComment::create([
                            'indexed_content_id' => $content->id,
                            'body' => $comment->body,
                            'author' => $comment->author,
                            'commented_at' => $comment->createdAt,
                            'comment_id' => $comment->id,
                            'metadata' => $comment,
                        ]);
                        IndexIssueComment::dispatch($issue, $indexedComment);
                    }
                }
            }
        }
    }

    private function extractTextFromDocument(array $array): string
    {
        $textParts = [];
        foreach ($array as $key => $value) {
            if ($key === 'text' && is_string($value)) {
                $textParts[] = $value;
            } elseif (is_array($value)) {
                $nestedText = $this->extractTextFromDocument($value);
                if (!empty($nestedText)) {
                    $textParts[] = $nestedText;
                }
            }
        }
        return implode(' ', $textParts);
    }

    private function issueNeedsIndexing(Issue $issue): bool
    {
        $existingContent = IndexedContent::query()
            ->where('source_id', $issue->id)
            ->first();

        if ($existingContent === null) {
            return true;
        }

        return $issue->getLastUpdatedAt()->gt($existingContent->indexed_at);
    }
}
