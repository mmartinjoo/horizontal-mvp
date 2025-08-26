<?php

namespace App\Integrations\Communication;

use Carbon\Carbon;

class IssueComment
{
    public function __construct(
        public string $id,
        public string $body,
        public string $author,
        public Carbon $createdAt,
    ) {
    }

    public function fromJira(array $data): self
    {
        return new static(
            id: $data['id'],
            body: $data['body'],
            author: $data['author']['displayName'],
            createdAt: Carbon::parse($data['created']),
        );
    }
}
