<?php

namespace App\Services\Indexing;

use Illuminate\Support\Facades\Http;

class EntityExtractor
{
    public function __construct(private string $url)
    {
    }

    public function extract(string $text): array
    {
        return Http::post($this->url . '/extract', [
            'text' => $text,
        ])
            ->throw()
            ->json();
    }
}
