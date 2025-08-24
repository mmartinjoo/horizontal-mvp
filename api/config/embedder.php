<?php

return [
    'provider' => env('EMBEDDER_PROVIDER', 'openai'),
    'model' => env('EMBEDDER_MODEL', 'text-embedding-3-large'),
    'max_input_tokens' => env('EMBEDDER_MAX_INPUT_TOKENS', 1024),
];
