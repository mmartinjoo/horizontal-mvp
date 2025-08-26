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
        Schema::create('indexed_content_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(IndexedContent::class)->constrained()->cascadeOnDelete();
            $table->longText('body');
            $table->string('author');
            $table->dateTime('commented_at');
            $table->string('comment_id');
            $table->jsonb('metadata')->nullable();
            $table->timestamps();
        });

        DB::statement("ALTER TABLE indexed_content_comments ADD COLUMN embedding vector(1536)");

        DB::statement("
          ALTER TABLE indexed_content_comments
          ADD COLUMN search_vector tsvector
          GENERATED ALWAYS AS (
              setweight(to_tsvector('english', body), 'A')
          ) STORED
      ");

        DB::statement("CREATE INDEX indexed_content_comments_search_vector_idx ON indexed_content_comments USING GIN(search_vector)");
        DB::statement("CREATE INDEX indexed_content_comments_embedding_idx ON indexed_content_comments USING hnsw (embedding vector_cosine_ops)");
    }

    public function down(): void
    {
        Schema::dropIfExists('indexed_content_comments');
    }
};
