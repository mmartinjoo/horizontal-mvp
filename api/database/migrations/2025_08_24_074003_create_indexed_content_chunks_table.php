<?php

use App\Models\IndexedContent;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('indexed_content_chunks', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(IndexedContent::class)->constrained()->cascadeOnDelete();
            $table->longText('body');
            $table->integer('position');

            $table->timestamps();
        });

        DB::statement("ALTER TABLE indexed_content_chunks ADD COLUMN embedding vector(1536)");

        DB::statement("
          ALTER TABLE indexed_content_chunks
          ADD COLUMN search_vector tsvector
          GENERATED ALWAYS AS (
              setweight(to_tsvector('english', body), 'A')
          ) STORED
      ");

        DB::statement("CREATE INDEX indexed_content_chunks_search_vector_idx ON indexed_content_chunks USING GIN(search_vector)");
        DB::statement("CREATE INDEX indexed_content_chunks_embedding_idx ON indexed_content_chunks USING hnsw (embedding vector_cosine_ops)");
    }

    public function down(): void
    {
        Schema::dropIfExists('indexed_content_chunks');
    }
};
