<?php

use App\Models\IndexedContentComment;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('indexed_content_comment_entities', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(IndexedContentComment::class)->constrained()->cascadeOnDelete();
            $table->jsonb('keywords');
            $table->jsonb('people');
            $table->jsonb('dates');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('indexed_content_comment_entities');
    }
};
