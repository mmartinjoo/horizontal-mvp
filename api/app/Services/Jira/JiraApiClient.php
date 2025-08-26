<?php

namespace App\Services\Jira;

use App\Models\JiraIntegration;
use App\Models\Team;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class JiraApiClient
{
    public function __construct(
        private JiraTokenManager $tokenManager
    ) {}

    /**
     * Make an authenticated request to the Jira API
     */
    public function makeRequest(Team $team, string $method, string $endpoint, array $data = []): Response
    {
        $integration = $this->getValidIntegration($team);

        $url = $this->buildApiUrl($integration, $endpoint);

        $response = Http::withToken($integration->access_token)
            ->acceptJson()
            ->{strtolower($method)}($url, $data);

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
                    ->{strtolower($method)}($url, $data);
            }
        }

        return $response;
    }

    /**
     * Get a list of projects for the team
     */
    public function getProjects(Team $team): array
    {
        $response = $this->makeRequest($team, 'GET', '/rest/api/3/project');

        if (!$response->successful()) {
            throw new \Exception('Failed to fetch Jira projects: ' . $response->body());
        }

        return $response->json();
    }

    /**
     * Get issues from a specific project
     */
    public function getIssues(Team $team, string $projectKey, array $options = []): array
    {
        $jql = "project = {$projectKey}";

        if (isset($options['status'])) {
            $jql .= " AND status = '{$options['status']}'";
        }

        $queryParams = array_merge([
            'jql' => $jql,
            'maxResults' => 50,
            'fields' => 'summary,status,assignee,created,updated',
        ], $options);

        $endpoint = '/rest/api/3/search?' . http_build_query($queryParams);
        $response = $this->makeRequest($team, 'GET', $endpoint);

        if (!$response->successful()) {
            throw new \Exception('Failed to fetch Jira issues: ' . $response->body());
        }

        return $response->json();
    }

    /**
     * Get current user information
     */
    public function getCurrentUser(Team $team): array
    {
        $response = $this->makeRequest($team, 'GET', '/rest/api/3/myself');

        if (!$response->successful()) {
            throw new \Exception('Failed to fetch current user: ' . $response->body());
        }

        return $response->json();
    }

    /**
     * Get issue comments
     */
    public function getIssueComments(Team $team, string $issueKey): array
    {
        $response = $this->makeRequest($team, 'GET', "/rest/api/3/issue/{$issueKey}/comment");

        if (!$response->successful()) {
            throw new \Exception('Failed to fetch issue comments: ' . $response->body());
        }

        return $response->json();
    }

    /**
     * Get a valid integration with fresh tokens
     */
    private function getValidIntegration(Team $team): JiraIntegration
    {
        $integration = JiraIntegration::where('team_id', $team->id)->first();

        if (!$integration) {
            throw new \Exception('No Jira integration found for team');
        }

        // Ensure token is valid (refresh if needed)
        if (!$this->tokenManager->ensureValidToken($integration)) {
            throw new \Exception('Unable to obtain valid Jira token');
        }

        return $integration->fresh(); // Reload in case token was refreshed
    }

    /**
     * Build the full API URL using Atlassian gateway
     */
    private function buildApiUrl(JiraIntegration $integration, string $endpoint): string
    {
        if (!$integration->cloud_id) {
            throw new \Exception('Cloud ID is required for Jira API calls');
        }
        
        $endpoint = ltrim($endpoint, '/');
        
        // Use Atlassian gateway API with cloud ID
        return 'https://api.atlassian.com/ex/jira/' . $integration->cloud_id . '/' . $endpoint;
    }
}
