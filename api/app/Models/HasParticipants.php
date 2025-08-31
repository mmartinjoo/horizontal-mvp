<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Arr;
use SebastianBergmann\Diff\Chunk;

trait HasParticipants
{
    public function participants(): MorphMany
    {
        return $this->morphMany(DocumentParticipant::class, 'entity');
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
                $this->participants()->create([
                    'name' => $person['name'],
                    'context' => Arr::get($person, 'context'),
                ]);
            }
        }
    }
}
