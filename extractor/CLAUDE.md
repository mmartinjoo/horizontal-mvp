# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This is a Python-based Named Entity Recognition (NER) API service built with Flask. It extracts entities (people, dates) from text using spaCy and generates keywords using the Fireworks AI API. The service is containerized with Docker and designed to run as a standalone microservice.

## Development Commands

### Local Development
```bash
# Install dependencies
pip install -r requirements.txt

# Run the Flask development server
python api.py
```

### Docker Development
```bash
# Build the Docker image
make build

# Run the containerized service
make run

# Build and run in one command
make up
```

The service runs on port 9998 when using Docker, exposing port 80 internally.

## Architecture

### Core Components

- **`api.py`** - Flask application with `/extract` endpoint that orchestrates entity extraction
- **`src/extractor.py`** - Contains `extract_entities()` for spaCy-based NER and `extract_keywords()` for AI-powered keyword generation
- **`src/services/fireworks.py`** - Fireworks AI API client for text completion/keyword generation

### API Contract

**POST /extract**
- Input: JSON with `text` field
- Output: JSON with `people`, `dates`, and `keywords` arrays
- Uses spaCy `en_core_web_sm` model for entity extraction
- Requires `FIREWORKS_API_KEY` and `FIREWORKS_MODEL` environment variables

### Dependencies

Key Python packages:
- Flask with Gunicorn for production serving
- spaCy with `en_core_web_sm` model for NER
- requests for Fireworks AI API calls
- python-dotenv for environment configuration

## Environment Configuration

Required environment variables:
- `FIREWORKS_API_KEY` - API key for Fireworks AI service
- `FIREWORKS_MODEL` - Model name to use for completions

## Deployment

The application is containerized using Python 3.12.8-slim base image and runs with Gunicorn on port 80 inside the container. The Makefile provides simple commands for building and running the Docker container locally.