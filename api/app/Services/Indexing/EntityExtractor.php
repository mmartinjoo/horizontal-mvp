<?php

namespace App\Services\Indexing;

use App\Services\LLM\Embedder;
use App\Services\LLM\LLM;

class EntityExtractor
{
    public function __construct(private LLM $llm, private Embedder $embedder)
    {
    }

    public function extractTopics(string $text): array
    {
        $prompt = "
            You are a topic extraction specialist for a knowledge management system that connects information across multiple tools (Slack, Gmail, Google Drive, GitHub, Linear, etc.).

            ## Horizontal app

            This is the application I'm building, and you are a part of.

            ### The problem
            Companies use lots of different applications to:
            - Store information
            - Communicate
            - Manage work

            These applications contain lots of knowledge, conversation, and other important information.
            I'm talking about apps like:
            - Gmail
            - Google Drive
            - Dropbox
            - Calendar
            - Notion
            - Trello
            - Linear
            - Jira
            - Slack
            - AWS S3
            - etc

            The problem is that it's really hard to get information and knowlegde because it's spread across so many different applications.

            For example, today, I had a meeting in my calendar with a guy called \"Dunsin.\" The event's name was: \"Dunsin x Martin\"
            I had no idea what was the topic of the meeting or who Dunsin was.

            Another example. Today I set up HTTPS with certbot for an application that runs in a docker compose stack. I already did this in the past, and I know I documentes the process. But I had no idea where the document was. So I had to figure out the entire process again.

            ### The solution
            Horizontal is an application that has all the knowledge about a team's work.
            It integrates all applications and gathers every piece of information and stores it in a central database.

            Whenever someone has a question, such as, \"who is Dunsin and why do I have a meeting with him?\" they can just ask the app and it answers: \"Dunsin is a PHP developer who works on an event scheduling application. You had conversation with him in Email and you aggreed to have a 60-min meeting to help him optimize the performance of his application.\"

            Or they can ask it \"how the hell do I set up certbot in docker compose stack?\" and it answers with my documentation that they created a year ago in a Google Drive document.

            Whenever they ask a question, it scans all the records and answers my question in the best possible way and points me to the original direction of the information. For example, if it lives if a Google Drive doc it gives me the link so I can check it out.

            ### The target audience

            Since I have an audience of 20,000 developers, first I want to target development teams.
            That means I'll integrate GitHub into the app, so it can understand the company's source code and PRs as well.

            For example, a developer can ask a question like \"Why do we have this weird workaround in feature XYZ?\"
            And the app will show:
            - GitHub PR comments
            - Slack conversations
            - Emails
            - Linear tickets

            Where they discussed why the workaround was necessary.

            So I want to target developer teams at first. The best customer would be a smaller, fast-moving startup where things are not that organized yet.

            Another great use case is something like this:
            - A critical bug appears in production
            - You need to find similar issues from the past
            - You dig through Jira tickets, GitHub issues, and Slack conversations
            - You spend an hour recreating context that already existed

            Users would be able to ask a simple question like: \"What was the root cause of the previous 'mysql server has gone away' error in prod?\"

            And Horizontal would answer like this:
            \"\"\"
            Your team had this issue on the 15th of April. The `max_allowed_packet` value was exceeded bacause of a very large `INSERT` in the bulk create API.

                Participants:

            Ben created the issues
            Tom contributed the fixes
            Peter merged the pull request


            GitHub PR #247 - \"Refactor bulk create API\" [Link to PR]
            Slack #engineering - Thread from April 15 [Link to Slack conversation]
            Linear ticket DEV-238 - Refactor bulk creation INSERT query [Link to Linear issue]
            Linear ticket DEV-240 - Increase MySQL `max_allowed_packet` [Link to Linear issue]
            \"\"\"

            ### The feedback

            This is the feedback on the idea from a potential customer:

            Me: What’s your biggest challenge when managing knowledge across tools like Google Drive, Slack, GitHub, Jira, Email, etc.?
            Potential customer: I think the biggest challenge i have would be switching between different tabs, apps and screens to get work done.

            Me: If you had a magic wand, what would you wish for to solve this problem?
            Potential customer: Focus. An all-in-one solution would let me stay in a single app that connects all my tools, so I don’t have to waste time switching between screens, apps, and dealing with team permissions .

            Me: If a tool could integrate ALL your apps into ONE smart knowledge base, how valuable would that be to you?
            Potential customer: 4 out of 5

            Me: What specific features would you expect from such a tool?
            Potential customer:
            1. Unified search across all teams😋
            2. Link sharing (something like creating a private link where specific members can access only certain things)
            3. Great UI, minimalistic and simple enough to use

            Me: What would make you hesitant to use a tool like this?
            Potential customer: Security, i wouldn't love a situation where my account would be hacked and i would lose access to all linked apps/tools

            Me: Would you pay for a tool like this?
            Potential customer: Yes

            Me: If yes, how much would you expect to pay per month?
            Potential customer: $10

            Some questions that potential customers would ask from a system like this:
            - What did I discuss with Samuel regarding Notifications last week?
            - Why does feature X not do XYZ yet?
            - Where is our coding style guideline file?
            - I need our logo
            - Did we solve all customer complaints today?
            - What was the root cause of the last “MySQL server has gone away” error?

            One of the most important technical parts of the application is a graph database with all the knowledge of a customer from every possible source.

            One of the most important aspects of this knowledge graph is topics and keywords. For example, if a developer team in the customer's company talks about \"Feature XYZ\" in a:
            * Slack conversation
            * Google Drive doc
            * Linear issue

            Your task: Extract domain-specific keywords and topics that will serve as connection points in a knowledge graph.

            ## What to Extract

            ### HIGH PRIORITY (Always extract these):
            - Project/feature names (e.g., \"Feature XYZ\", \"bulk create API\", \"notification system\")
            - Technical components and systems (e.g., \"MySQL server\", \"docker compose stack\", \"certbot\")
            - Error messages and issues (e.g., \"MySQL server has gone away\", \"max_allowed_packet exceeded\")
            - Internal tools, services, and codebases
            - Internal terminology and acronyms unique to the organization

            ### MEDIUM PRIORITY:
            - API endpoints and database tables
            - Configuration settings and environment variables
            - Meeting titles and recurring events
            - Document titles and their main topics
            - Ticket/issue IDs with context (e.g., \"DEV-238\", \"PR #247\")

            ### LOW/SKIP:
            - Generic programming terms (unless specifically discussed as a topic)
            - Common words that appear everywhere
            - Generic business terms unless they're part of a specific initiative

            ## Extraction Rules

            1. **Preserve exact terminology**: Keep the exact phrasing used in the organization (e.g., \"bulk create API\" not just \"API\")

            2. **Capture variations**: Include common variations and abbreviations
                - If text mentions \"notification system\", \"notifs\", and \"notification service\" - note all variations

            3. **Maintain context**: For ambiguous terms, include enough context to distinguish them
                - Not just \"pipeline\" but \"CI/CD pipeline\" or \"data pipeline\"

            4. **Link related concepts**: Group closely related topics
                - \"MySQL error\" + \"max_allowed_packet\" + \"bulk insert issue\" could all relate to the same incident

            5. **Time-sensitive topics**: For incidents or time-bound issues, preserve temporal context
                - \"April 15 production outage\" not just \"production outage\"

            ## Output Format

            Return a JSON structure:
            ```json
            {
              \"topics\": [
                {
                  \"name\": \"exact phrase from text\",
                  \"variations\": [\"alternate names\", \"abbreviations\"],
                  \"category\": \"feature|person|issue|tool|incident|process\",
                  \"importance\": \"high|medium|low\"
                }
              ]
            }
            ```

            ## Content-Specific Guidelines

            **For Slack/Teams conversations**: Focus on decision points, problem descriptions, and action items
            **For documentation**: Extract section headers, defined terms, and process names
            **For GitHub PRs/issues**: Focus on module names, function purposes, and architectural decisions
            **For Jira/Linear tickets/issues**: Extract problem statements, affected systems, and resolution approaches
            **For emails**: Extract meeting purposes, project names, and commitments

            Remember: The goal is to enable queries like \"What was the root cause of the MySQL has gone away error?\" or \"Who worked on the notification system?\" Your extracted topics should make these connections possible.

            Text:
            \"\"\"
            $text
            \"\"\"
        ";

        $response = $this->llm->completion($prompt, 4096);
        $json = json_decode($response, true);
        $data =  !$json
            ? []
            : $json;

        foreach ($data['topics'] as $i => $topic) {
            $embedding = $this->embedder->createEmbedding(
                sprintf("%s %s %s", $topic['name'], $topic['category'], implode($topic['variations']))
            );
            $data['topics'][$i]['embedding'] = $embedding;
        }
        return $data;
    }

    public function extractParticipants(string $text): array
    {
        $prompt = "
            You are a person and entity extraction specialist for a knowledge management system that connects information across multiple tools (Slack, Gmail, Google Drive, GitHub, Linear, Jira, etc.).

            ## Horizontal app

            This is the application I'm building, and you are a part of.

            ### The problem
            Companies use lots of different applications to:
            - Store information
            - Communicate
            - Manage work

            These applications contain lots of knowledge, conversation, and other important information.
            I'm talking about apps like:
            - Gmail
            - Google Drive
            - Dropbox
            - Calendar
            - Notion
            - Trello
            - Linear
            - Jira
            - Slack
            - AWS S3
            - etc

            The problem is that it's really hard to get information and knowlegde because it's spread across so many different applications.

            For example, today, I had a meeting in my calendar with a guy called \"Dunsin.\" The event's name was: \"Dunsin x Martin\"
            I had no idea what was the topic of the meeting or who Dunsin was.

            Another example. Today I set up HTTPS with certbot for an application that runs in a docker compose stack. I already did this in the past, and I know I documentes the process. But I had no idea where the document was. So I had to figure out the entire process again.

            ### The solution
            Horizontal is an application that has all the knowledge about a team's work.
            It integrates all applications and gathers every piece of information and stores it in a central database.

            Whenever someone has a question, such as, \"who is Dunsin and why do I have a meeting with him?\" they can just ask the app and it answers: \"Dunsin is a PHP developer who works on an event scheduling application. You had conversation with him in Email and you aggreed to have a 60-min meeting to help him optimize the performance of his application.\"

            Or they can ask it \"how the hell do I set up certbot in docker compose stack?\" and it answers with my documentation that they created a year ago in a Google Drive document.

            Whenever they ask a question, it scans all the records and answers my question in the best possible way and points me to the original direction of the information. For example, if it lives if a Google Drive doc it gives me the link so I can check it out.

            ### The target audience

            Since I have an audience of 20,000 developers, first I want to target development teams.
            That means I'll integrate GitHub into the app, so it can understand the company's source code and PRs as well.

            For example, a developer can ask a question like \"Why do we have this weird workaround in feature XYZ?\"
            And the app will show:
            - GitHub PR comments
            - Slack conversations
            - Emails
            - Linear tickets

            Where they discussed why the workaround was necessary.

            So I want to target developer teams at first. The best customer would be a smaller, fast-moving startup where things are not that organized yet.

            Another great use case is something like this:
            - A critical bug appears in production
            - You need to find similar issues from the past
            - You dig through Jira tickets, GitHub issues, and Slack conversations
            - You spend an hour recreating context that already existed

            Users would be able to ask a simple question like: \"What was the root cause of the previous 'mysql server has gone away' error in prod?\"

            And Horizontal would answer like this:
            \"\"\"
            Your team had this issue on the 15th of April. The `max_allowed_packet` value was exceeded bacause of a very large `INSERT` in the bulk create API.

                Participants:

            Ben created the issues
            Tom contributed the fixes
            Peter merged the pull request


            GitHub PR #247 - \"Refactor bulk create API\" [Link to PR]
            Slack #engineering - Thread from April 15 [Link to Slack conversation]
            Linear ticket DEV-238 - Refactor bulk creation INSERT query [Link to Linear issue]
            Linear ticket DEV-240 - Increase MySQL `max_allowed_packet` [Link to Linear issue]
            \"\"\"

            ### The feedback

            This is the feedback on the idea from a potential customer:

            Me: What’s your biggest challenge when managing knowledge across tools like Google Drive, Slack, GitHub, Jira, Email, etc.?
            Potential customer: I think the biggest challenge i have would be switching between different tabs, apps and screens to get work done.

            Me: If you had a magic wand, what would you wish for to solve this problem?
            Potential customer: Focus. An all-in-one solution would let me stay in a single app that connects all my tools, so I don’t have to waste time switching between screens, apps, and dealing with team permissions .

            Me: If a tool could integrate ALL your apps into ONE smart knowledge base, how valuable would that be to you?
            Potential customer: 4 out of 5

            Me: What specific features would you expect from such a tool?
            Potential customer:
            1. Unified search across all teams😋
            2. Link sharing (something like creating a private link where specific members can access only certain things)
            3. Great UI, minimalistic and simple enough to use

            Me: What would make you hesitant to use a tool like this?
            Potential customer: Security, i wouldn't love a situation where my account would be hacked and i would lose access to all linked apps/tools

            Me: Would you pay for a tool like this?
            Potential customer: Yes

            Me: If yes, how much would you expect to pay per month?
            Potential customer: $10

            Some questions that potential customers would ask from a system like this:
            - What did I discuss with Samuel regarding Notifications last week?
            - Why does feature X not do XYZ yet?
            - Where is our coding style guideline file?
            - I need our logo
            - Did we solve all customer complaints today?
            - What was the root cause of the last “MySQL server has gone away” error?

            One of the most important technical parts of the application is a graph database with all the knowledge of a customer from every possible source.

            One of the most important aspects of this knowledge graph is topics and keywords. For example, if a developer team in the customer's company talks about \"Feature XYZ\" in a:
            * Slack conversation
            * Google Drive doc
            * Linear issue

            Your task: Extract all people, companies, and organizational entities that appear in the text to build a comprehensive contact and relationship graph.

            ## What to Extract

            ### PEOPLE CATEGORIES:

            **Team Members (Internal)**
            - @mentions (e.g., @JohnDoe, @john.doe, @jdoe)
            - Email addresses ending with company domain
            - Names appearing in assignee/reviewer/author fields
            - Names with associated roles (e.g., \"Sarah from DevOps\", \"Tom, our CTO\")

            **External Contacts**
            - Client/customer names
            - Vendor/partner contacts
            - Consultants and contractors
            - Names with external company associations

            **Ambiguous Names**
            - First names only (determine if team member based on context)
            - Nicknames and informal references
            - Names without clear affiliation

            ### ORGANIZATIONS:

            **Companies**
            - Client/customer companies
            - Partner organizations
            - Vendor/supplier companies
            - Competitors mentioned
            - Previous employers of team members

            **Internal Teams/Departments**
            - Engineering, Sales, Marketing, etc.
            - Specific team names (e.g., \"Platform Team\", \"Mobile Squad\")

            ## Extraction Rules

            1. **Platform-Specific Patterns**:
               - **Slack**: @mentions, <@U1234567> (user IDs), \"cc @channel\", display names
               - **GitHub**: @username, \"Co-authored-by:\", PR reviewers, issue assignees
               - **Jira**: Reporter, Assignee, Watchers, @mentions in comments
               - **Email**: From, To, CC fields, signatures, \"regards, [name]\"
               - **Docs**: Author metadata, \"Last edited by\", @mentions in comments

            2. **Context Clues for Role Identification**:

            \"reached out to [Name]\" → likely external
            \"[Name] fixed the bug\" → likely team member
            \"meeting with [Name] from [Company]\" → external contact
            \"[Name] merged the PR\" → team member with write access
            \"as [Name] suggested in standup\" → team member

            3. **Email Address Parsing**:
            - john.doe@ourcompany.com → Internal team member \"John Doe\"
            - client@externalcompany.com → External contact
            - noreply@, support@, info@ → System/generic addresses (note but flag as non-person)

            4. **Name Variation Handling**:
            - \"John\", \"John D.\", \"John Doe\", \"JD\", \"@jdoe\" → might all be the same person
            - Capture all variations but note potential matches

            5. **Relationship Extraction**:
            - \"[Person] works at [Company]\"
            - \"[Person] from the [Team] team\"
            - \"[Person], our [Role]\"
            - \"[Person1] introduced us to [Person2]\"

            ## Special Cases

            **Handle These Carefully**:
            - Generic names in examples (e.g., \"John Doe\" in documentation)
            - Placeholder names (e.g., \"USER\", \"CUSTOMER\", \"[Name]\")
            - Bot/system accounts (e.g., \"github-bot\", \"jira-automation\")
            - Group mentions (@channel, @here, @everyone, \"the team\")
            - Don't extract company names that are likely just internal tools such as Jira, Slack, GitHub, etc. These are not real \"contacts\" in most cases

            **Confidence Indicators**:
            - HIGH: Full name with email or @mention
            - MEDIUM: Full name with context
            - LOW: First name only, ambiguous references

            ## Output Format

            Return a JSON structure:
            ```json
            {
            \"people\": [
             {
               \"name\": \"primary name used\",
               \"context\": \"how they were mentioned\",
               \"confidence\": \"high|medium|low\"
             }
            ],
            \"organizations\": [
             {
               \"name\": \"Company/Organization Name\",
               \"context\": \"how it was mentioned\",
               \"confidence\": \"high|medium|low\"
             }
            ],
            }
            ```

            ## Content-Specific Guidelines
            For Slack messages: Focus on @mentions, reaction authors, thread participants
            For GitHub: PR authors, reviewers, commenters, commit authors
            For Jira/Linear: Reporter, assignee, watchers, mentioned in description/comments
            For Emails: Sender, recipients, CC'd parties, mentioned in body, signatures
            For Meetings/Calendar: Attendees, organizer, mentioned in agenda/notes
            For Documents: Authors, editors, commenters, mentioned in content

            **Critical Instructions**
            - Preserve exact spelling: Don't correct perceived typos in names
            - Maintain privacy awareness: Flag if sensitive personal information appears
            - Track first appearances: Note where/when someone is first mentioned
            - Distinguish humans from bots: Identify automated accounts
            - Cultural sensitivity: Handle names from all cultures appropriately
            - Don't include the character @ from mentions. If a mention says \"@john.doe\" only extract \"john.doe\"

            Remember: The goal is to build a comprehensive contact graph that can answer questions like \"Who worked on this feature?\", \"Who is our contact at Company X?\", or \"Who did Sarah introduce us to last month?\"

            Text:
            \"\"\"
            $text
            \"\"\"
        ";

        $response = $this->llm->completion($prompt, 4096);
        $json = json_decode($response, true);
        return !$json
            ? []
            : $json;
    }
}
