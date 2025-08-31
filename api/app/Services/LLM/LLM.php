<?php

namespace App\Services\LLM;

use Illuminate\Support\Str;

abstract class LLM
{
    public function __construct(
        protected string $apiKey,
        protected string $model,
    ) {}

    abstract public function completion(string $prompt, $maxTokens = 1024): string;

    protected function sanitizeJSON(string $message): string
    {
        $sanitizedText = Str::trim($message);
        if (Str::contains($sanitizedText, '```json')) {
            $sanitizedText = substr($sanitizedText, strpos($sanitizedText, '```json') + 7);
        }
        if (Str::contains($sanitizedText, '```')) {
            $sanitizedText = substr($sanitizedText, 0, strpos($sanitizedText, '```'));
        }
        return Str::trim($sanitizedText);
    }
}
