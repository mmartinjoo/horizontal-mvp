<?php

use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('CREATE EXTENSION IF NOT EXISTS vector');

        Schema::create('indexed_contents', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Team::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(User::class)->nullable()->constrained()->cascadeOnDelete();

            $table->string('source_type');
            $table->string('source_id');
            $table->string('source_url')->nullable();

            $table->mediumText('title');
            $table->longText('body')->nullable();
            $table->longText('preview')->nullable();

            $table->string('priority')->nullable();

            $table->jsonb('metadata')->nullable();

            $table->dateTime('indexed_at')->nullable();
            $table->timestamps();
        });

        DB::statement("ALTER TABLE indexed_contents ADD COLUMN embedding vector(1536)");

        DB::statement("
          ALTER TABLE indexed_contents
          ADD COLUMN search_vector tsvector
          GENERATED ALWAYS AS (
              setweight(to_tsvector('english', COALESCE(title, '')), 'A') ||
              setweight(to_tsvector('english', body), 'B')
          ) STORED
      ");

        DB::statement("CREATE INDEX indexed_contents_search_vector_idx ON indexed_contents USING GIN(search_vector)");
        DB::statement("CREATE INDEX indexed_contents_embedding_idx ON indexed_contents USING hnsw (embedding vector_cosine_ops)");
    }

    public function down(): void
    {
        Schema::dropIfExists('indexed_contents');
    }
};
