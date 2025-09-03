<?php

use App\Services\GraphDB\GraphDB;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $labels = ['Project', 'Issue', 'IssueChunk', 'IssueComment', 'IssueWorklog', 'Topic', 'Participant', 'File', 'FileChunk'];
        $graphDB = app(GraphDB::class);
        foreach ($labels as $label) {
            $labelLower = strtolower($label);
//            $graphDB->query("DROP VECTOR INDEX IF EXISTS vector_index_$labelLower ON :$label;");
            $graphDB->query("
                CREATE VECTOR INDEX vector_index_$labelLower
                ON :$label(embedding)
                WITH CONFIG {\"dimension\": 1536, \"capacity\": 1024};"
            );
        }
    }
};
