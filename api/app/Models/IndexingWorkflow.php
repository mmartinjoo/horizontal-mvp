<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IndexingWorkflow extends Model
{
    protected $guarded = [];

    public function items()
    {
        return $this->hasMany(IndexingWorkflowItem::class);
    }
}
