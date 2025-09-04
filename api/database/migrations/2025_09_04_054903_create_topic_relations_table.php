<?php

use App\Models\Topic;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('topic_relations', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Topic::class, 'topic_id')->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Topic::class, 'related_topic_id')->constrained()->cascadeOnDelete();
            $table->float('similarity');
            $table->timestamps();

            $table->unique(['topic_id', 'related_topic_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('topic_relations');
    }
};
