<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IndexedContent extends Model implements Embeddable
{
    use HasFactory;
    use HasEmbedding;

    protected $guarded = [];

    protected $hidden = [
        'embedding',
        'search_vector',
    ];

    protected $casts = [
        'metadata' => 'array',
        'embedding' => 'array',
    ];
}
