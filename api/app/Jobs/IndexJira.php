<?php

namespace App\Jobs;

use App\Integrations\Communication\Issue;
use App\Integrations\Communication\IssueComment;
use App\Integrations\Communication\Jira\Jira;
use App\Models\Document;
use App\Models\DocumentComment;
use App\Models\IndexingWorkflow;
use App\Models\IndexingWorkflowItem;
use App\Models\JiraIntegration;
use App\Models\JiraProject;
use App\Models\Participant;
use App\Models\Team;
use App\Services\GraphDB\GraphDB;
use App\Services\LLM\Embedder;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Str;

class IndexJira implements ShouldQueue
{
    use Queueable;

    public function __construct(private Team $team)
    {
    }

    public function handle(
        Jira $jira,
        GraphDB $graphDB,
        Embedder $embedder,
    ): void {
        /** @var IndexingWorkflow $indexing */
        $indexing = IndexingWorkflow::create([
            'integration' => 'jira',
            'status' => 'syncing',
            'team_id' => $this->team->id,
            'job_id' => $this->job->payload()['uuid'],
        ]);

        $jiraIntegration = JiraIntegration::where('team_id', $this->team->id)->firstOrFail();
        $projects = $jira->getProjects($this->team);
        foreach ($projects as $projectData) {
            $project = JiraProject::query()
                ->where('key', $projectData['key'])
                ->where('team_id', $this->team->id)
                ->first();

            if (!$project) {
                $embedding = $embedder->createEmbedding($projectData['name']);
                $project = JiraProject::create([
                    'team_id' => $this->team->id,
                    'jira_integration_id' => $jiraIntegration->id,
                    'title' => $projectData['name'],
                    'key' => $projectData['key'],
                    'jira_id' => $projectData['id'],
                    'embedding' => $embedding,
                ]);
                $graphDB->createNode('Project', [
                    'id' => $project->id,
                    'name' => $project->title,
                    'embedding' => $embedding,
                ]);
            }

            $prios = ['high', 'medium', 'low'];
            foreach ($prios as $prio) {
                $dateRange = match($prio) {
                    'high' => ['from' => now()->subMonths(1), 'to' => now()],
                    'medium' => ['from' => now()->subMonths(3), 'to' => now()->subMonths(1)],
                    'low' => ['from' => now()->subMonths(6), 'to' => now()->subMonths(3)],
                };
                $issues = $jira->getIssues($this->team, $projectData['key'], $dateRange['from'], $dateRange['to']);
                $indexing->increment('overall_items', count($issues));
                foreach ($issues as $i => $issueData) {
                    $description = $this->extractTextFromDocument($issueData['fields']['description'] ?? []);
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
                    $count = Document::query()
                        ->where('team_id', $this->team->id)
                        ->where('source_type', 'jira')
                        ->where('source_id', $issue->id)
                        ->delete();

                    $indexing->increment('deleted_items', $count);
                    $embedding = $embedder->createEmbedding($issue->title);
                    $doc = Document::create([
                        'team_id' => $this->team->id,
                        'source_type' => 'jira',
                        'source_id' => $issue->id,
                        'source_url' => $issue->url,
                        'title' => $issue->title,
                        'priority' => $prio,
                        'metadata' => $issueData,
                        'embedding' => $embedding,
                    ]);
                    $graphDB->createNodeWithRelation(
                        newNodeLabel: 'Issue',
                        newNodeAttributes: [
                            'id' => $doc->id,
                            'name' => $doc->title,
                            'embedding' => $embedding,
                        ],
                        relation: 'PART_OF',
                        relatedNodeLabel: 'Project',
                        relatedNodeID: $project->id,
                    );
                    $indexingItem = IndexingWorkflowItem::create([
                        'indexing_workflow_id' => $indexing->id,
                        'data' => $issue,
                        'status' => 'queued',
                        'document_id' => $doc->id,
                    ]);
                    IndexIssue::dispatch($issue, $indexingItem->id);

                    $commentsData = $jira->getIssueComments($this->team, $issue);
                    $bodies = [];
                    foreach ($commentsData as $commentData) {
                        $bodies[] = $this->extractTextFromDocument($commentData['body'] ?? []);
                    }
                    $comments = IssueComment::collectJira($commentsData, $bodies);
                    foreach ($comments as $comment) {
                        $p = Participant::updateOrCreate(
                            [
                                'slug' => Str::slug($comment->author),
                                'type' => 'person',
                            ],
                            [
                                'slug' => Str::slug($comment->author),
                                'name' => $comment->author,
                                'type' => 'person',
                            ],
                        );
                        $indexedComment = DocumentComment::create([
                            'document_id' => $doc->id,
                            'author_id' => $p->id,
                            'body' => $comment->body,
                            'commented_at' => $comment->createdAt,
                            'comment_id' => $comment->id,
                            'metadata' => $comment,
                        ]);
                        IndexIssueComment::dispatch($indexedComment);
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
        $existingContent = Document::query()
            ->where('source_id', $issue->id)
            ->first();

        if ($existingContent === null) {
            return true;
        }

        return $issue->getLastUpdatedAt()->gt($existingContent->indexed_at ?? Carbon::parse('1900-01-01 00:00:00'));
    }
}
