<?php

use App\Models\DocumentComment;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_comment_entities', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(DocumentComment::class)->constrained()->cascadeOnDelete();
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
        Schema::dropIfExists('document_comment_entities');
    }
};
