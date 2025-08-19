<?php

return [
    'provider' => env('EMBEDDER_PROVIDER', 'openai'),
    'model' => env('EMBEDDER_MODEL', 'text-embedding-3-large'),
];
