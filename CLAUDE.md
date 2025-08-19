# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Structure

This is a full-stack knowledge search platform with two main components:

- **`api/`** - Laravel backend API (see `api/CLAUDE.md` for detailed backend guidance)
- **`frontend/`** - Frontend application (structure to be explored)

## Quick Start

For backend development, work from the `api/` directory:
```bash
cd api/
composer run dev  # Starts Laravel server, queue worker, logs, and Vite
```

For comprehensive backend development guidance including architecture, services, and database patterns, refer to `api/CLAUDE.md`.

## Development Workflow

When working on this codebase:

1. **Backend changes**: Work in `api/` directory and follow Laravel conventions outlined in `api/CLAUDE.md`
2. **Frontend changes**: Work in `frontend/` directory  
3. **Full-stack features**: Coordinate changes across both directories

## Key Technologies

- **Backend**: Laravel 12, PostgreSQL with pgvector, Redis queues
- **Frontend**: To be determined based on exploration
- **AI/ML**: Multiple LLM providers (Anthropic, OpenAI, Fireworks) for embeddings and search
- **Vector Search**: PostgreSQL with pgvector extension for semantic search

## Important Notes

- This is a multi-tenant application scoped by teams
- All content indexing happens asynchronously via background jobs
- The system integrates with external services (GitHub, Slack, Linear) for content ingestion
- Vector embeddings and full-text search are combined for hybrid search functionality

For detailed Laravel backend development guidance, always reference `api/CLAUDE.md` which contains comprehensive architecture details, development commands, and patterns specific to this application.