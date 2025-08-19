# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This is a Laravel application called "Project X" - a knowledge search platform that integrates multiple data sources (GitHub, Slack, Linear, etc.) to create a unified search experience across team knowledge. The app uses vector embeddings and full-text search to help teams find information scattered across different tools.

## Development Commands

### Core Laravel Commands
- `composer run dev` - Start full development environment (server, queue, logs, vite)
- `php artisan serve` - Start Laravel development server only
- `php artisan queue:listen --tries=1` - Start queue worker for background jobs
- `php artisan pail --timeout=0` - Real-time log monitoring
- `npm run dev` - Start Vite development server for assets
- `npm run build` - Build production assets

### Testing & Quality
- `composer run test` - Run PHPUnit tests (clears config first)
- `php artisan test` - Run tests directly
- `vendor/bin/pint` - Run Laravel Pint code formatter

### Database
- `php artisan migrate` - Run database migrations
- `php artisan migrate:fresh --seed` - Fresh migration with seeding
- `php artisan db:seed` - Run database seeders

### Queue Management
- `make worker-dev` - Start queue worker with development settings
- `php artisan queue:work --timeout=120 --max-jobs=100` - Production queue worker

## Architecture Overview

### Core Concepts
- **Content Ingestion**: Integrates with external services (GitHub, Slack, Linear) to pull data
- **Vector Search**: Uses PostgreSQL with pgvector extension for semantic search
- **Full-text Search**: PostgreSQL's tsvector for keyword-based search
- **Hybrid Search**: Combines vector similarity with keyword matching
- **Background Processing**: Queue-based embedding generation for content

### Key Models
- `Content` - Central model storing all indexed content with embeddings
- `Integration` - Manages OAuth connections to external services  
- `Team` - Multi-tenant organization structure
- `User` - Team members with role-based access

### Service Architecture
- **LLM Services** (`app/Services/LLM/`):
  - `LLMFactory` - Creates LLM instances (Anthropic, OpenAI, Fireworks)
  - `Embedder` - Generates vector embeddings for content
- **Vector Store Services** (`app/Services/VectorStore/`):
  - `VectorStoreFactory` - Creates vector store instances (Postgres, Pinecone)
  - Database uses pgvector extension with HNSW indexing

### Database Structure
- **contents** table: Core content storage with vector embeddings and full-text search
- **integrations** table: OAuth tokens and sync metadata for external services
- **teams** table: Multi-tenant organization structure
- Uses PostgreSQL with pgvector extension for vector operations
- Generated tsvector columns for full-text search with weighted rankings

### Configuration Files
- `config/llm.php` - LLM provider and model configuration
- `config/vector_store.php` - Vector database driver selection
- `config/embedder.php` - Embedding model configuration

## Key Patterns

### Factory Pattern Usage
- `LLMFactory::create()` - Get configured LLM instance
- `LLMFactory::createEmbedder()` - Get embedding service
- `VectorStoreFactory::create()` - Get vector store instance

### Background Job Processing
- `CreateEmbeddingJob` - Asynchronously generates embeddings for content
- Jobs are queued when new content is ingested from integrations

### Multi-tenant Design
- All content is scoped to teams via foreign keys
- Integrations are team-specific with separate OAuth tokens

## Environment Variables

Key environment variables to configure:
- `LLM_PROVIDER` - Set to 'anthropic', 'openai', or 'fireworks' 
- `LLM_MODEL` - Model identifier for chosen provider
- `VECTOR_STORE_DRIVER` - Set to 'postgres' or 'pinecone'
- OAuth credentials for integrations (GitHub, Slack, etc.)

## Database Requirements

- PostgreSQL with pgvector extension enabled
- Vector operations use cosine similarity
- Full-text search uses English language configuration
- HNSW indexing for efficient vector similarity search