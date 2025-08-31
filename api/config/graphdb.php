<?php

return [
    'default' => env('GRAPH_DB_CONNECTION', 'memgraph'),

    'connections' => [
        'memgraph' => [
            'host'     => env('GRAPH_DB_HOST', '127.0.0.1'),
            'port'     => env('GRAPH_DB_PORT', 7687),
            'user'     => env('GRAPH_DB_USER', null),
            'password' => env('GRAPH_DB_PASSWORD', null),
            'scheme'   => 'bolt', // Or 'bolt+s' for SSL
        ],
    ],
];
