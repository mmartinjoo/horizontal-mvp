<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Arr;

trait HasTopics
{
    public function topics(): MorphMany
    {
        return $this->morphMany(DocumentTopic::class, 'entity');
    }

    public function createTopics(array $topics)
    {
        foreach ($topics as $topic) {
            if (!Arr::get($topic, 'name')) {
                continue;
            }
            if (Arr::get($topic, 'importance', 'low') === 'low') {
                continue;
            }
            $this->topics()->create([
                'name' => $topic['name'],
                'variations' => $topic['variations'],
                'category' => $topic['category'],
            ]);
        }
    }
}
