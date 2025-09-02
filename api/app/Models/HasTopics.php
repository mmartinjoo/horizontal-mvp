<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

trait HasTopics
{
    public function topics(): MorphToMany
    {
        return $this
            ->morphToMany(Topic::class, 'entity', 'documents_topics')
            ->withTimestamps();
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
            $t = Topic::updateOrCreate(
                [
                    'slug' => Str::slug($topic['name']),
                ],
                [
                    'slug' => Str::slug($topic['name']),
                    'name' => $topic['name'],
                    'variations' => $topic['variations'],
                    'category' => $topic['category'],
                ],
            );
            $this->topics()->attach($t->id);
        }
    }
}
