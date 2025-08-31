<?php

namespace App\Services\LLM;

use Exception;
use Illuminate\Support\Facades\Http;

class Fireworks extends LLM
{
    public function completion(string $prompt, $maxTokens = 1024): string
    {
        $res = Http::withHeaders([
            'Authorization' => 'Bearer '.$this->apiKey,
        ])
            ->timeout(300)
            ->post('https://api.fireworks.ai/inference/v1/completions', [
                    'model' => $this->model,
                    'prompt' => $prompt,
                    'max_tokens' => $maxTokens,
                    'temperature' => 0.4,
                ],
            )
            ->throw()
            ->json();

        if (empty($res['choices'][0]['text'])) {
            throw new Exception('Fireworks: No completion found: '.json_encode($res));
        }

        return $this->sanitizeJSON($res['choices'][0]['text']);
    }
}
