<?php

namespace App\Services\LLM;

use Exception;
use OpenAI as VendorOpenAI;
use OpenAI\Client;

class OpenAI extends LLM implements Embedder
{
    private Client $client;

    public function __construct(
        protected string $apiKey,
        protected string $model,
    ) {
        parent::__construct($apiKey, $model);
        $this->client = VendorOpenAI::client($this->apiKey);
    }

    public function completion(string $prompt, $maxTokens = 1024): string
    {
        $result = $this->client->chat()->create([
            'model' => $this->model,
            'messages' => [
                ['role' => 'user', 'content' => $prompt],
            ],
            'max_tokens' => $maxTokens,
        ]);

        return $result->choices[0]->message->content;
    }

    public function createEmbedding(string $text): array
    {
        $response = $this->client->embeddings()->create([
            'input' => $text,
            'model' => config('embedder.model'),
        ]);

        if (! $response->embeddings[0]->embedding) {
            throw new Exception('Embedder: No embeddings found.');
        }

        return $response->embeddings[0]->embedding;
    }
}
