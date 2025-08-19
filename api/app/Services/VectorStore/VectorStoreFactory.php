<?php

namespace App\Services\VectorStore;

use App\Services\LLM\Anthropic;

class VectorStoreFactory
{
    public static function create(): VectorStore
    {
        return match (config('vector_store.driver')) {
            'pinecone' => new Pinecone(config('services.pinecone.api_key')),
            default => new Postgres(),
        };
    }
}
