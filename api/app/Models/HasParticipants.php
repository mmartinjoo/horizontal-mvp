<?php

namespace App\Models;

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
                    ],
                );
                $this->participants()->attach($p->id, [
                    'context' => Arr::get($person, 'context'),
                ]);
            }
        }
    }
}
