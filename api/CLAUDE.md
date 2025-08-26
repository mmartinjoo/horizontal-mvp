# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Development Commands

**Start development environment** (recommended):
```bash
composer run dev
```
This starts Laravel server, queue worker, logs, and Vite concurrently.

**Alternative individual commands**:
```bash
php artisan serve              # Start Laravel server
php artisan queue:work         # Start queue worker
php artisan pail               # View logs in real-time
npm run dev                    # Start Vite for asset compilation
```

**Testing**:
```bash
composer run test              # Run full test suite
php artisan test               # Alternative test command
php artisan test --filter=TestName  # Run specific test
```

**Queue Management**:
```bash
make worker                    # Start queue worker with production settings
php artisan queue:listen --tries=1  # Start queue listener (dev)
php artisan queue:restart      # Restart queue workers
```

**Code Quality**:
```bash
./vendor/bin/pint              # Fix PHP code style
php artisan config:clear       # Clear cached config
php artisan migrate:fresh --seed  # Fresh database with seeders
```

## Architecture Overview

This is a **knowledge search platform** with AI-powered content indexing and semantic search capabilities.

### Core Domain Concepts

**Content Indexing Pipeline**:
- `IndexingWorkflow` → `IndexingWorkflowItem` → Async job processing
- Files are downloaded, parsed, chunked, embedded, and stored in vector database
- Supports PDF parsing and text chunking with configurable strategies

**Multi-LLM Architecture**:
- Factory pattern for LLM providers (Anthropic, OpenAI, Fireworks)
- Separate embedding service (primarily OpenAI)
- Configurable via `config/llm.php` and `config/embedder.php`

**Vector Storage**:
- Supports PostgreSQL (with pgvector) and Pinecone
- Factory pattern in `VectorStoreFactory`
- Embeddings stored alongside searchable metadata

### Key Service Patterns

**LLM Services** (`app/Services/LLM/`):
- `LLMFactory::create()` - Creates LLM instance based on config
- `LLMFactory::createEmbedder()` - Creates embedding service
- Provider classes: `Anthropic`, `OpenAI`, `Fireworks`

**Vector Storage** (`app/Services/VectorStore/`):
- `VectorStoreFactory::create()` - Creates vector store based on config
- `Postgres` and `Pinecone` implementations

**Content Processing**:
- `TextChunker` - Breaks content into embeddable chunks
- `PdfParser` - Extracts text from PDF files stream-wise
- `FilePrioritizer` - Determines indexing priority

### Database Architecture

**Core Models**:
- `Team` - Multi-tenant scoping
- `IndexedContent` - Main content entities with embeddings
- `IndexedContentChunk` - Text chunks for semantic search
- `IndexingWorkflow` / `IndexingWorkflowItem` - Async processing tracking
- `Integration` - External service connections

**Key Traits**:
- `Embeddable` - Interface for embeddable content
- `HasEmbedding` - Trait for embedding functionality

### Job Processing

**Main Jobs**:
- `IndexFile` - Downloads, processes, and indexes individual files
- `EmbedContentJob` - Creates embeddings for content
- `PrepareContentChunks` - Chunks content for embedding

**Processing Flow**:
1. File queued via `IndexFile` job
2. Downloaded from external source (Google Drive)
3. Content extracted and chunked
4. Each chunk embedded and stored in vector database
5. Workflow status updated throughout process

### Integration Layer

**Storage Integrations**:
- `GoogleDrive` - Google Drive file access
- `File` - Generic file abstraction

**Configuration**:
- Multi-provider setup with environment-based switching
- Separate configs for LLM, embedding, and vector storage
- Service credentials in `config/services.php`

## Testing Strategy

- PHPUnit with SQLite in-memory database
- Feature tests in `tests/Feature/`
- Unit tests in `tests/Unit/`
- Queue jobs run synchronously in test environment

## Important Patterns

**Factory Pattern**: Used extensively for LLM and VectorStore creation to support multiple providers.

**Job Batching**: Content processing uses Laravel's job batching for coordinated async operations.

**Exception Handling**: Custom exceptions for domain-specific errors (`EmbeddingException`, `IndexingException`, `NoContentToIndexException`).

**Multi-Tenancy**: All content is scoped to Teams for proper data isolation.

**Streaming Processing**: PDF parsing uses generators for memory-efficient processing of large files.