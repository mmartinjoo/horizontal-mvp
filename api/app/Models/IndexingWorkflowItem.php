<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class IndexingWorkflowItem extends Model
{
    protected $guarded = [];

    protected $casts = [
        'data' => 'array',
        'job_ids' => 'array',
    ];

    public function indexing_workflow()
    {
        return $this->belongsTo(IndexingWorkflow::class);
    }

    public function indexed_content(): BelongsTo
    {
        return $this->belongsTo(IndexedContent::class);
    }

    public function user(): HasManyThrough
    {
        return $this->hasManyThrough(User::class, IndexingWorkflow::class);
    }
}
