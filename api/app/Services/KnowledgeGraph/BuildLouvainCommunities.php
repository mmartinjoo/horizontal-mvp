<?php

namespace App\Services\KnowledgeGraph;

use App\Jobs\IndexGraphCommunity;
use App\Services\GraphDB\GraphDB;
use Bolt\protocol\v5\structures\Node;

class BuildLouvainCommunities
{
    public function __construct(private GraphDB $graphDB)
    {
    }

    public function build()
    {
        $this->graphDB->run("
            match p=(n)-[r]-(m)
            where (not n:Chunk) and (not m:Chunk)
            with project(p) as subgraph
            CALL community_detection.get(subgraph)
            YIELD node, community_id
            MERGE (c:Community {id: community_id, name: community_id})
            MERGE (node)-[:BELONGS_TO]->(c);
        ");
    }

    public function index()
    {
        $communities = $this->graphDB->queryMany("
            match (c:Community)
            return c
        ", ['c']);

        foreach ($communities as $community) {
            $summary = "";
            $nodes = $this->graphDB->queryMany("
                match (n:__Entity__)-[r:BELONGS_TO]->(c:Community)
                where c.id = {$community->properties['id']}
                return n
            ", ['n']);

            foreach ($nodes as $node) {
                $summary .= "{$node->properties['name']}; ";
            }

            $nodeIds = [];
            foreach ($nodes as $node) {
                $nodeIds[] = $node->id;
            }
            $nodeIdsStr = json_encode($nodeIds);

            /** @var Node $chunk */
            $chunk = $this->graphDB->query("
                match (n:__Entity__)<-[r:MENTIONS]-(c:Chunk)
                where id(n) IN {$nodeIdsStr}
                return c
            ", 'c');
            $context = $chunk ? $chunk->properties['text'] : "";

            IndexGraphCommunity::dispatch(
                $community->properties['id'],
                $summary,
                $context,
            );
        }
    }
}
