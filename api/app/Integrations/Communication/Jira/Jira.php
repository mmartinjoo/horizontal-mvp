<?php

namespace App\Integrations\Communication\Jira;

use App\Models\JiraIntegration;
use App\Models\Team;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class Jira
{
    public function __construct(
        private JiraTokenManager $tokenManager
    ) {}

    public function makeRequest(Team $team, string $endpoint): Response
    {
        $integration = $this->getValidIntegration($team);

        $url = $this->buildApiUrl($integration, $endpoint);

        $response = Http::withToken($integration->access_token)
            ->acceptJson()
            ->throw()
            ->get($url);

        // If token is invalid, try to refresh and retry once
        if ($response->status() === 401) {
            Log::info('Jira API returned 401, attempting token refresh', [
                'team_id' => $team->id,
                'integration_id' => $integration->id,
            ]);

            if ($this->tokenManager->refreshToken($integration)) {
                // Retry with refreshed token
                $integration->refresh();
                $response = Http::withToken($integration->access_token)
                    ->acceptJson()
                    ->get($url);
            }
        }

        return $response;
    }

    public function getProjects(Team $team): array
    {
        $response = $this->makeRequest($team, '/rest/api/3/project');

        if (!$response->successful()) {
            throw new Exception('Failed to fetch Jira projects: ' . $response->body());
        }

        return $response->json();
    }

    public function getIssues(Team $team, string $projectKey, Carbon $from, Carbon $to): array
    {
        $jql = "project={$projectKey} and created>=\"2025-08-26\"";

        $queryParams = [
            'jql' => $jql,
            'maxResults' => 10,
            'fields' => 'summary,status,assignee,created,updated,description',
        ];

        $endpoint = '/rest/api/3/search/jql?jql=project%3DCPG&fields=summary,status,assignee,created,updated';
        $response = $this->makeRequest($team, $endpoint);

        if (!$response->successful()) {
            throw new Exception('Failed to fetch Jira issues: ' . $response->body());
        }

        return $response->json('issues');
    }

    public function getCurrentUser(Team $team): array
    {
        $response = $this->makeRequest($team, '/rest/api/3/myself');

        if (!$response->successful()) {
            throw new Exception('Failed to fetch current user: ' . $response->body());
        }

        return $response->json();
    }

    public function getIssueComments(Team $team, string $issueKey): array
    {
        $response = $this->makeRequest($team, "/rest/api/3/issue/{$issueKey}/comment");

        if (!$response->successful()) {
            throw new Exception('Failed to fetch issue comments: ' . $response->body());
        }

        return $response->json();
    }

    private function getValidIntegration(Team $team): JiraIntegration
    {
        $integration = JiraIntegration::where('team_id', $team->id)->first();

        if (!$integration) {
            throw new Exception('No Jira integration found for team');
        }

        // Ensure token is valid (refresh if needed)
        if (!$this->tokenManager->ensureValidToken($integration)) {
            throw new Exception('Unable to obtain valid Jira token');
        }

        return $integration->fresh(); // Reload in case token was refreshed
    }

    private function buildApiUrl(JiraIntegration $integration, string $endpoint): string
    {
        if (!$integration->cloud_id) {
            throw new Exception('Cloud ID is required for Jira API calls');
        }

        $endpoint = ltrim($endpoint, '/');

        return 'https://api.atlassian.com/ex/jira/' . $integration->cloud_id . '/' . $endpoint;
    }
}
