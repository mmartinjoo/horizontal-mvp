<?php

namespace App\Services\KnowledgeGraph;

use App\Services\GraphDB\GraphDB;

class KnowledgeGraph
{
    public function __construct(private GraphDB $graphDB)
    {

    }

    public function buildCommunities()
    {
        // Putting nodes into level 0 communities:
        $this->graphDB->run("
            match p=(n)-[r]-(m)
            where (not n:Chunk) and (not m:Chunk)
            with project(p) as subgraph
            CALL leiden_community_detection.get(subgraph)
            YIELD node, communities
            WITH node, communities[0] AS first_community
            MERGE (c:Community {id: first_community, level: 0})
            MERGE (node)-[:BELONGS_TO]->(c);
        ");

        // Construct community hierarchy
        $this->graphDB->run("
            match p=(n)-[r]-(m)
            where (not n:Chunk) and (not m:Chunk)
            with project(p) as subgraph
            call leiden_community_detection.get(subgraph)
            yield node, community_id, communities
            with collect({node: node, communities: communities}) as all_results
            unwind all_results as result
            with result.communities as communities
            unwind range(0, size(communities) - 2) as level
            with distinct
                communities[level] as child_id,
                communities[level + 1] as parent_id,
                level
            merge (child:Community {id: child_id, level: level})
            merge (parent:Community {id: parent_id, level: level + 1})
            merge (child)-[:CHILD_OF]->(parent);
        ");
    }
}
