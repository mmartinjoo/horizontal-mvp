<?php

use App\Services\GraphDB\GraphDB;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $graphDB = app(GraphDB::class);
//        $graphDB->query("
//            CREATE VECTOR INDEX vector_index_entities
//            ON :__Entity__(embedding)
//            WITH CONFIG {\"dimension\": 1536, \"capacity\": 20000};"
//        );
        $graphDB->query("
            CREATE VECTOR INDEX vector_index_communities
            ON :Community(embedding)
            WITH CONFIG {\"dimension\": 1536, \"capacity\": 20000};"
        );
    }
};
