<?php

namespace App\Services\KnowledgeGraph;

use App\Services\GraphDB\GraphDB;
use App\Services\LLM\Embedder;
use App\Services\LLM\LLM;
use Bolt\protocol\v5\structures\Node;

class KnowledgeGraph
{
    public function __construct(
        private GraphDB $graphDB,
        private LLM $llm,
        private Embedder $embedder,
    ) {
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
            MERGE (c:Community {id: first_community, level: 0, name: first_community})
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
            merge (child:Community {id: child_id, level: level, name: child_id})
            merge (parent:Community {id: parent_id, level: level + 1, name: parent_id})
            merge (child)-[:CHILD_OF]->(parent);
        ");
    }

    public function indexCommunities()
    {
        $results = $this->graphDB->run("
            match (c:Community)<-[r:BELONGS_TO]-(n)
            return c.id as community_id, id(n) as node_id, n.name as node_name
        ");

        $map = [];
        foreach ($results as $result) {
            if (!isset($map[$result['community_id']])) {
                $map[$result['community_id']] = [
                    'node_names' => '',
                    'nodes' => [],
                    'original_text' => ''
                ];
            }
            $map[$result['community_id']]['nodes'][] = [
                'node_id' => $result['node_id'],
                'node_name' => $result['node_name'],
            ];
            $map[$result['community_id']]['node_names'] .= "{$result['node_name']}; ";

            /** @var Node $chunk */
            $chunk = $this->graphDB->query("
                match (n)<-[r:MENTIONS]-(c:Chunk)
                where id(n) = {$result['node_id']}
                return c
            ", 'c');
            if (empty($chunk)) {
                continue;
            }
            $map[$result['community_id']]['original_text'] .= "{$chunk->properties['text']}";
        }
        dd($map);
    }
}
