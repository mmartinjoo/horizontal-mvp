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

The problem is that it's really hard to get information and knowlegde because it's spread across so many different applications.

For example, today, I had a meeting in my calendar with a guy called "Dunsin." The event's name was: "Dunsin x Martin"
I had no idea what was the topic of the meeting or who Dunsin was.

Another example. Today I set up HTTPS with certbot for an application that runs in a docker compose stack. I already did this in the past, and I know I documentes the process. But I had no idea where the document was. So I had to figure out the entire process again.

## The solution
I want to build an application that has all the knowlegde about my life including:
- Business
- Work
- Personal

Whenever I have a question, such as, "who is Dunsin and why do I have a meeting with him?" I can just ask the app and it answers: "Dunsin is a PHP developer who works on an event scheduling application. You had conversation with him on Twitter and you aggreed to have a 60-min meeting to help him optimize the performance of his application."

Or I can ask it "how the hell do I set up certbot in docker compose stack?" and it answers with my documentation that I created a year ago in a Google Drive document.

The app integrates all application and gathers every piece of information and stores it in a central database.
Whenever I ask a question it scans all the records and answers my question in the best possible way and points me to the original direction of the information. For example, if it lives if a Google Drive doc it gives me the link so I can check it out.

## The challenge
How can I integrate all of these apps into one platform?
Does Zapier offer integrations like these?

I would need to scan and store gigabytes or petabyrtes of data into a central database, I guess.

## Existing products
- Dropbox Dash
- Glean

## Potential use cases
"Who the fuck wrote this and why?" - Every developer's daily nightmare. Not just git blame, but the actual Slack conversation where someone explained why they used Redis instead of Postgres, or the Linear ticket that explains why this weird workaround exists. This alone would sell it.

The Production Fire Drill - "We had this exact bug 6 months ago, I know someone fixed it, but I can't find the PR or the discussion." Your tool instantly surfaces the PR, the Slack thread, and the postmortem doc.

The Undocumented Tribal Knowledge - "Sarah always handles the Stripe webhooks" but Sarah just quit. Now you need every Slack message, every PR comment, every doc Sarah ever touched about payments.

Architecture Decision Archaeology - New dev: "Why do we have 3 different authentication systems?" Your tool: "Here's the 2019 Slack thread, the failed migration PR, and the Confluence doc explaining the technical debt."

## MVP

The MVP would focus on 3-5 KEY integrations.

Start with GitHub + Slack + Linear/Jira. Here's why:
GitHub + Slack = The holy grail combo. Every engineering decision lives between these two. PRs reference Slack threads, Slack discusses PRs. This alone solves "why was this built this way?"

Linear/Jira = The context layer. Links the "what we built" (GitHub) with "why we built it" (tickets) and "how we decided" (Slack).
Skip Confluence initially - most teams barely update it anyway.

The Search Flow:
- User asks: "Why weird solution in MatchingEngine?"
- Generate embedding of question
- Vector similarity search across all content
- Find connected documents (if PR matches, grab linked Slack threads)
- Pass top 10 results to GPT-4 to synthesize answer
- Return answer with links to sources

**The Indexing Strategy**
Yes, index everything upfront - but be smart about it:
Initial Sync (when user connects):

GitHub: Index last 6 months of activity initially, backfill older content gradually
Slack: Last 90 days first (most Slack plans limit history anyway), then backfill
Linear: All open issues + closed from last 3 months

Here's why: Users expect immediate results after connecting. "Your search will work in 24 hours" = lost customer. But indexing 5 years of Slack history before they can search anything? Also a lost customer.
The Progressive Enhancement Approach:

Storage strategy:
- Store raw API responses in S3/R2 (cheap, for reprocessing)
- Store processed/indexed data in PostgreSQL
- Generate embeddings asynchronously (don't block initial sync)

**The Search Architecture Decision**
Use BOTH - here's the hybrid approach that actually works:

Phase 1: Keyword Search (MVP - Week 1)
sql-- PostgreSQL full-text search
SELECT * FROM indexed_content 
WHERE search_vector @@ plainto_tsquery('MatchingEngine weird solution')
ORDER BY ts_rank(search_vector, query) DESC;
Fast, cheap, good enough for exact matches. Gets you to market.
Phase 2: Hybrid Search (Month 2-3)
python# When user searches:
1. Keyword search (PostgreSQL FTS) - weight: 0.3
2. Vector similarity (pgvector) - weight: 0.7
3. Combine scores, re-rank results
4. Boost results with cross-references (PR + linked Slack = higher score)
Why hybrid beats pure vector search:

Searching for "PR #1234" → keyword search finds it instantly
Searching for "why did we choose Redis over Postgres" → vector search understands intent
Searching for "MatchingEngine" (specific term) → keyword ensures exact match isn't missed

## Syncing new data
After the initial data sync we need to sync every new changes from 3rd parties. It can be done via webhooks.

## Database structure

```sql
-- Teams & Auth
CREATE TABLE teams (
    id UUID PRIMARY KEY,
    name TEXT NOT NULL,
    created_at TIMESTAMPTZ DEFAULT NOW(),
);

CREATE TABLE integrations (
    id UUID PRIMARY KEY,
    team_id UUID REFERENCES teams(id),
    service TEXT NOT NULL, -- 'github', 'slack', 'linear'
    access_token TEXT ENCRYPTED,
    refresh_token TEXT ENCRYPTED,
    webhook_secret TEXT,
    last_sync_at TIMESTAMPTZ,
    sync_status TEXT DEFAULT 'pending'
);

-- Unified Content Table (The Magic)
CREATE TABLE indexed_content (
    id UUID PRIMARY KEY,
    team_id UUID REFERENCES teams(id),
    
    -- Source tracking
    source_type TEXT NOT NULL, -- 'github_pr', 'github_commit', 'slack_message', 'linear_issue'
    source_id TEXT NOT NULL, -- Original ID from service
    source_url TEXT, -- Direct link back to source
    
    -- Content
    title TEXT, -- PR title, issue title, or NULL for messages
    body TEXT NOT NULL, -- The actual content
    author_email TEXT,
    author_name TEXT,
    
    -- Metadata
    metadata JSONB, -- Service-specific data
    created_at TIMESTAMPTZ,
    updated_at TIMESTAMPTZ,
    
    -- Search vectors
    search_vector tsvector GENERATED ALWAYS AS (
        setweight(to_tsvector('english', COALESCE(title, '')), 'A') ||
        setweight(to_tsvector('english', body), 'B')
    ) STORED,
    
    embedding vector(1536), -- OpenAI ada-002 dimensions
    
    -- Indexes
    UNIQUE(team_id, source_type, source_id)
);

CREATE INDEX idx_content_search ON indexed_content USING GIN(search_vector);
CREATE INDEX idx_content_embedding ON indexed_content USING ivfflat(embedding vector_cosine_ops);
CREATE INDEX idx_content_team_source ON indexed_content(team_id, source_type, created_at DESC);

-- Cross-references (The Secret Sauce)
CREATE TABLE content_references (
    id UUID PRIMARY KEY,
    from_content_id UUID REFERENCES indexed_content(id),
    to_content_id UUID REFERENCES indexed_content(id),
    reference_type TEXT, -- 'mentions', 'links_to', 'resolves', 'discusses'
    confidence FLOAT DEFAULT 1.0,
    UNIQUE(from_content_id, to_content_id, reference_type)
);

CREATE INDEX idx_references_from ON content_references(from_content_id);
CREATE INDEX idx_references_to ON content_references(to_content_id);
```

mappings
```sql
-- Examples of how different content types map

-- GitHub PR → indexed_content
INSERT INTO indexed_content (
    source_type: 'github_pr',
    source_id: '1234',
    source_url: 'https://github.com/org/repo/pull/1234',
    title: 'Add caching to MatchingEngine',
    body: 'This PR adds Redis caching to improve performance...',
    metadata: {
        repo: 'matching-engine',
        state: 'merged',
        merged_at: '2024-01-15',
        files_changed: ['engine.go', 'cache.go'],
        labels: ['performance', 'backend']
    }
);

-- Slack Message → indexed_content  
INSERT INTO indexed_content (
    source_type: 'slack_message',
    source_id: 'C234234.234234',
    source_url: 'https://workspace.slack.com/archives/C234234/p234234',
    title: NULL,
    body: 'We should use Redis here because the Postgres query is too slow',
    metadata: {
        channel: 'engineering',
        thread_ts: '234234.234',
        reactions: ['thumbsup', 'eyes'],
        mentions: ['U234234']
    }
);
```

querí layer

```sql
-- For caching and query optimization
CREATE TABLE search_cache (
    id UUID PRIMARY KEY,
    team_id UUID REFERENCES teams(id),
    query_text TEXT,
    query_embedding vector(1536),
    results JSONB,
    created_at TIMESTAMPTZ DEFAULT NOW()
);

-- Track what's connected to what
CREATE MATERIALIZED VIEW content_graph AS
SELECT 
    ic.id,
    ic.source_type,
    ic.title,
    array_agg(DISTINCT cr.to_content_id) AS connected_ids,
    count(DISTINCT cr.to_content_id) AS connection_count
FROM indexed_content ic
LEFT JOIN content_references cr ON ic.id = cr.from_content_id
GROUP BY ic.id, ic.source_type, ic.title;

CREATE INDEX idx_graph_connections ON content_graph(connection_count DESC);
```

## Marketing

Headline: Your team already solved this problem. 
          You just can't find where.

Subhead: Connect GitHub, Slack, and Linear. 
         Search everything. Ship faster.

The Workflow Priority Matrix
![Alt text for the image](/Users/joomartin/code/getyourshittogether/ideation/images/1.png)


The Marketing Hook for Each
- "Turn your git blame into git understand"
- "Your team already fixed this bug. Find out how."
- "Onboard developers in days, not weeks"
- "Every line of code comes with its conversation"
- "Never repeat the Kubernetes evaluation again"
