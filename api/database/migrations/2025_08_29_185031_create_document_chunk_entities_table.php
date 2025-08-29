<?php

use App\Models\DocumentChunk;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_chunk_entities', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(DocumentChunk::class)->constrained()->cascadeOnDelete();
            $table->jsonb('keywords');
            $table->jsonb('people');
            $table->jsonb('dates');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_chunk_entities');
    }
};
