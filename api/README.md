1. Environment Setup

First, make sure you have the required environment variables in
your .env file:

# Add these to your .env file
JIRA_CLIENT_ID=your_actual_jira_client_id
JIRA_CLIENT_SECRET=your_actual_jira_client_secret
JIRA_REDIRECT_URI=http://localhost:8000/api/integrations/jira/o
auth/callback

Getting Jira OAuth Credentials:
1. Go to https://developer.atlassian.com/console/myapps/
2. Create a new app or use existing one
3. Add OAuth 2.0 (3LO) authorization
4. Set redirect URL to:
   http://localhost:8000/api/integrations/jira/oauth/callback
5. Copy the Client ID and Client Secret

2. Start Your Development Environment

# Start Laravel with queue worker, logs, and Vite
composer run dev

# Or individually:
php artisan serve              # Starts server on
http://localhost:8000
php artisan queue:work         # For background jobs
php artisan pail               # For real-time logs

3. Create Test User and Team

Since the system requires authentication, create a test user
with a team:

php artisan tinker

// In Tinker console:
$team = \App\Models\Team::create(['name' => 'Test Company']);

$user = \App\Models\User::create([
'name' => 'Test User',
'email' => 'test@example.com',
'password' => bcrypt('password'),
'team_id' => $team->id
]);

// Create an API token for testing
$token = $user->createToken('test-token')->plainTextToken;
echo "API Token: " . $token;

4. Test the OAuth Flow

Step 1: Check Integration Status

curl -X GET
"http://localhost:8000/api/integrations/jira/oauth/status" \
-H "Authorization: Bearer YOUR_API_TOKEN" \
-H "Accept: application/json"

Should return: {"connected": false, "message": "No Jira
integration found for this team"}

Step 2: Initiate OAuth Flow

curl -X POST
"http://localhost:8000/api/integrations/jira/oauth/authorize" \
-H "Authorization: Bearer YOUR_API_TOKEN" \
-H "Content-Type: application/json" \
-H "Accept: application/json" \
-d '{"jira_base_url": "https://your-company.atlassian.net"}'

Response should include:
{
"authorization_url":
"https://auth.atlassian.com/authorize?...",
"state": "random_40_char_string"
}

Step 3: Complete OAuth in Browser

1. Copy the authorization_url from the response
2. Open it in your browser
3. Login to your Atlassian account
4. Grant permissions
5. You'll be redirected back to your callback URL

Step 4: Verify Integration

curl -X GET
"http://localhost:8000/api/integrations/jira/oauth/status" \
-H "Authorization: Bearer YOUR_API_TOKEN" \
-H "Accept: application/json"

Should now return:
{
"connected": true,
"integration": {
"id": 1,
"jira_base_url": "https://your-company.atlassian.net",
"expires_at": "2025-08-26T10:34:21.000000Z",
"scope": ["read:jira-user", "read:jira-work"],
"is_token_valid": true,
"is_expired": false
},
"status": "active"
}

5. Test Token Management

php artisan tinker

// Test token refresh
$integration = \App\Models\JiraIntegration::first();
$tokenManager =
app(\App\Services\Jira\JiraTokenManager::class);

// Check if token is valid
$tokenManager->ensureValidToken($integration);

// Force refresh (for testing)
$tokenManager->refreshToken($integration);

// Test cleanup methods
$tokenManager->cleanupExpiredTokens();
$tokenManager->cleanupInvalidTokens();

6. Test Disconnection

curl -X DELETE
"http://localhost:8000/api/integrations/jira/oauth/disconnect"
\
-H "Authorization: Bearer YOUR_API_TOKEN" \
-H "Accept: application/json"

7. Monitor Logs

Watch the logs to see the OAuth flow in action:
tail -f storage/logs/laravel.log

Look for log entries like:
- "Refreshing Jira access token"
- "Jira integration successfully connected"
- "Token refresh completed"

8. Database Inspection

Check the database to verify data is stored correctly:

php artisan tinker

// View integrations
\App\Models\JiraIntegration::with('team')->get();

// Check encrypted tokens (they should be encrypted in DB but
decrypted when accessed)
$integration = \App\Models\JiraIntegration::first();
echo "Access Token (decrypted): " . $integration->access_token;
echo "Refresh Token (decrypted): " .
$integration->refresh_token;

9. Error Testing

Test error scenarios:

# Test invalid Jira URL
curl -X POST
"http://localhost:8000/api/integrations/jira/oauth/authorize" \
-H "Authorization: Bearer YOUR_API_TOKEN" \
-H "Content-Type: application/json" \
-d '{"jira_base_url": "https://invalid-domain.com"}'

# Test duplicate integration (after first one is created)
curl -X POST
"http://localhost:8000/api/integrations/jira/oauth/authorize" \
-H "Authorization: Bearer YOUR_API_TOKEN" \
-H "Content-Type: application/json" \
-d '{"jira_base_url": "https://your-company.atlassian.net"}'

10. Test with Postman/Insomnia (Optional)

Import this collection for easier testing:

{
"name": "Jira OAuth Integration",
"requests": [
{
"name": "Check Status",
"method": "GET",
"url":
"http://localhost:8000/api/integrations/jira/oauth/status",
"headers": {
"Authorization": "Bearer {{token}}",
"Accept": "application/json"
}
},
{
"name": "Start OAuth",
"method": "POST",
"url":
"http://localhost:8000/api/integrations/jira/oauth/authorize",
"headers": {
"Authorization": "Bearer {{token}}",
"Content-Type": "application/json"
},
"body": {
"jira_base_url": "https://your-company.atlassian.net"
}
},
{
"name": "Disconnect",
"method": "DELETE",
"url":
"http://localhost:8000/api/integrations/jira/oauth/disconnect",
"headers": {
"Authorization": "Bearer {{token}}",
"Accept": "application/json"
}
}
]
}

That's the complete testing workflow! The key is getting the
Jira OAuth credentials set up correctly, then you can test the
full flow end-to-end. Let me know if you run into any issues!
🚀
