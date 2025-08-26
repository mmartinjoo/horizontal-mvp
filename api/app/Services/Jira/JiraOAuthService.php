<?php

namespace App\Services\Jira;

use App\Models\JiraIntegration;
use App\Models\Team;
use Exception;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class JiraOAuthService
{
    private const ATLASSIAN_OAUTH_BASE_URL = 'https://auth.atlassian.com/authorize';
    private const ATLASSIAN_TOKEN_URL = 'https://auth.atlassian.com/oauth/token';
    private const ATLASSIAN_API_BASE_URL = 'https://api.atlassian.com';

    private array $scopes = [
        'read:jira-user',
        'read:jira-work',
    ];

    public function __construct(
        private string $clientId,
        private string $clientSecret,
        private string $redirectUri
    ) {}

    public function generateAuthorizationUrl(string $jiraBaseUrl, ?string $state = null): array
    {
        $state = $state ?: Str::random(40);

        $queryParams = http_build_query([
            'audience' => 'api.atlassian.com',
            'client_id' => $this->clientId,
            'scope' => implode(' ', $this->scopes),
            'redirect_uri' => $this->redirectUri,
            'state' => $state,
            'response_type' => 'code',
            'prompt' => 'consent',
        ]);

        return [
            'authorization_url' => self::ATLASSIAN_OAUTH_BASE_URL . '?' . $queryParams,
            'state' => $state,
            'jira_base_url' => $this->normalizeJiraUrl($jiraBaseUrl),
        ];
    }

    public function exchangeCodeForToken(string $code, string $state): array
    {
        $response = Http::asForm()->post(self::ATLASSIAN_TOKEN_URL, [
            'grant_type' => 'authorization_code',
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'code' => $code,
            'redirect_uri' => $this->redirectUri,
        ]);

        if (!$response->successful()) {
            throw new Exception('Failed to exchange authorization code for token: ' . $response->body());
        }

        return $response->json();
    }

    public function getAccessibleResources(string $accessToken): array
    {
        $response = Http::withToken($accessToken)
            ->get(self::ATLASSIAN_API_BASE_URL . '/oauth/token/accessible-resources');

        if (!$response->successful()) {
            throw new Exception('Failed to get accessible resources: ' . $response->body());
        }

        return $response->json();
    }

    public function refreshAccessToken(string $refreshToken): array
    {
        $response = Http::asForm()->post(self::ATLASSIAN_TOKEN_URL, [
            'grant_type' => 'refresh_token',
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'refresh_token' => $refreshToken,
        ]);

        if (!$response->successful()) {
            throw new Exception('Failed to refresh access token: ' . $response->body());
        }

        return $response->json();
    }

    public function normalizeJiraUrl(string $jiraUrl): string
    {
        // Remove trailing slashes
        $jiraUrl = rtrim($jiraUrl, '/');

        // Add https:// if no protocol specified
        if (!str_starts_with($jiraUrl, 'http://') && !str_starts_with($jiraUrl, 'https://')) {
            $jiraUrl = 'https://' . $jiraUrl;
        }

        // Validate it's an Atlassian domain
        $parsedUrl = parse_url($jiraUrl);
        if (!$parsedUrl || !isset($parsedUrl['host'])) {
            throw new \InvalidArgumentException('Invalid Jira URL provided');
        }

        $host = $parsedUrl['host'];
        if (!str_ends_with($host, '.atlassian.net')) {
            throw new \InvalidArgumentException('Only Atlassian Cloud instances are supported');
        }

        return $jiraUrl;
    }

    public function validateState(string $providedState, string $expectedState): bool
    {
        return hash_equals($expectedState, $providedState);
    }
}
