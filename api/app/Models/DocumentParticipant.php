<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class DocumentParticipant extends Model
{
    protected $guarded = [];

    public function entity(): MorphTo
    {
        return $this->morphTo();
    }
}
