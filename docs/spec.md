# horizontal.app

## The problem
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

For example, today, I had a meeting in my calendar with a guy called "Dunsin." The event's name was: "Dunsin x Martin"
I had no idea what was the topic of the meeting or who Dunsin was.

Another example. Today I set up HTTPS with certbot for an application that runs in a docker compose stack. I already did this in the past, and I know I documentes the process. But I had no idea where the document was. So I had to figure out the entire process again.

## The solution
Horizontal is an application that has all the knowledge about a team's work.
It integrates all applications and gathers every piece of information and stores it in a central database.

Whenever someone has a question, such as, "who is Dunsin and why do I have a meeting with him?" they can just ask the app and it answers: "Dunsin is a PHP developer who works on an event scheduling application. You had conversation with him in Email and you aggreed to have a 60-min meeting to help him optimize the performance of his application."

Or they can ask it "how the hell do I set up certbot in docker compose stack?" and it answers with my documentation that they created a year ago in a Google Drive document.

Whenever they ask a question, it scans all the records and answers my question in the best possible way and points me to the original direction of the information. For example, if it lives if a Google Drive doc it gives me the link so I can check it out.

## The target audience

Since I have an audience of 20,000 developers, first I want to target development teams.
That means I'll integrate GitHub into the app, so it can understand the company's source code and PRs as well.

For example, a developer can ask a question like "Why do we have this weird workaround in feature XYZ?"
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

Users would be able to ask a simple question like: "What was the root cause of the previous 'mysql server has gone away' error in prod?"

And Horizontal would answer like this:
"""
Your team had this issue on the 15th of April. The `max_allowed_packet` value was exceeded bacause of a very large `INSERT` in the bulk create API.

Participants:

Ben created the issues
Tom contributed the fixes
Peter merged the pull request


GitHub PR #247 - "Refactor bulk create API" [Link to PR]
Slack #engineering - Thread from April 15 [Link to Slack conversation]
Linear ticket DEV-238 - Refactor bulk creation INSERT query [Link to Linear issue]
Linear ticket DEV-240 - Increase MySQL `max_allowed_packet` [Link to Linear issue]
"""

## The feedback

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
