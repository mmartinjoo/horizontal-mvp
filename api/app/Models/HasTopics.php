<?php

namespace App\Models;

use App\Services\LLM\Embedder;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

trait HasTopics
{
    public function topics(): MorphToMany
    {
        return $this
            ->morphToMany(Topic::class, 'entity', 'documents_topics')
            ->withPivot('context', 'embedding')
            ->withTimestamps();
    }

    public function createTopics(array $topics)
    {
        $embedder = app(Embedder::class);
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
                    'embedding' => $topic['embedding'],
                ],
            );
            $embedding = Arr::get($topic, 'context')
                ? $embedder->createEmbedding(Arr::get($topic, 'context'))
                : [];
            $this->topics()->attach($t->id, [
                'context' => Arr::get($topic, 'context'),
                'embedding' => json_encode($embedding),
            ]);
        }
    }
}
