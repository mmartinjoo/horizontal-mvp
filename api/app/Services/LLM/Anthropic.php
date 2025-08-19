<?php

namespace App\Services\LLM;

use Exception;
use GuzzleHttp\Client;

class Anthropic extends LLM
{
    private $client;

    public function __construct(
        protected string $apiKey,
        protected string $model,
    ) {
        parent::__construct($apiKey, $model);
        $this->client = new Client([
            'base_uri' => 'https://api.anthropic.com/',
            'timeout' => 300,
        ]);
    }

    public function completion(string $prompt, $maxTokens = 1024): string
    {
        $response = $this->client->post('v1/messages', [
            'headers' => [
                'Content-Type' => 'application/json',
                'x-api-key' => $this->apiKey,
                'anthropic-version' => '2023-06-01',
            ],
            'json' => [
                'model' => $this->model,
                'max_tokens' => $maxTokens,
                'messages' => [
                    ['role' => 'user', 'content' => $prompt],
                ],
            ],
        ]);

        $result = json_decode($response->getBody(), true);

        $content = $result['content'];
        if (! is_array($content) || ! $content[0]) {
            throw new Exception('Invalid response from LLM. $result = '.json_encode($result));
        }

        $response = $content[0];
        if ($response['type'] !== 'text' || ! $response['text']) {
            throw new Exception('Invalid response from LLM. $response = '.json_encode($response));
        }

        return $response['text'];
    }
}
