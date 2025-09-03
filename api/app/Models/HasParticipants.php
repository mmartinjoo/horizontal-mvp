<?php

namespace App\Models;

use App\Services\LLM\Embedder;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

trait HasParticipants
{
    public function participants(): MorphToMany
    {
        return $this
            ->morphToMany(Participant::class, 'entity', 'documents_participants')
            ->withTimestamps();
    }

    public function createParticipants(array $participants)
    {
        $embedder = app(Embedder::class);
        $keys = ['people', 'organizations'];
        foreach ($keys as $key) {
            foreach ($participants[$key] as $person) {
                if (Arr::get($person, 'confidence') === 'low') {
                    continue;
                }
                if (!Arr::get($person, 'name')) {
                    continue;
                }
                $type = $key === 'people' ? 'person' : 'organization';
                $p = Participant::updateOrCreate(
                    [
                        'slug' => Str::slug($person['name']),
                        'type' => $type,
                    ],
                    [
                        'slug' => Str::slug($person['name']),
                        'name' => $person['name'],
                        'type' => $type,
                        'embedding' => $person['embedding'],
                    ],
                );
                $embedding = Arr::get($person, 'context')
                    ? $embedder->createEmbedding(Arr::get($person, 'context'))
                    : [];
                $this->participants()->attach($p->id, [
                    'context' => Arr::get($person, 'context'),
                    'embedding' => json_encode($embedding),
                ]);
            }
        }
    }
}
