<?php

namespace App\Services\LLM;

abstract class LLM
{
    public function __construct(
        protected string $apiKey,
        protected string $model,
    ) {}

    abstract public function completion(string $prompt, $maxTokens = 1024): string;
}
