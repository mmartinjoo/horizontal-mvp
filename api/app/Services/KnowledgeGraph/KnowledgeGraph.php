<?php

namespace App\Services\KnowledgeGraph;

use App\Jobs\IndexGraphCommunity;
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
        $alreadyIndexedCommunities = [];
        $communities = $this->graphDB->run("
            match (c:Community)
            return c.id as community_id, c.level as community_level
        ");

        foreach ($communities as $community) {
            if (in_array($community['community_id'] . '-' . $community['community_level'], $alreadyIndexedCommunities)) {
                continue;
            }

            $summaryText = "";
            $context = "";
            if ($community['community_level'] === 0) {
                $nodes = $this->graphDB->queryMany("
                    match (n:__Entity__)-[r:BELONGS_TO]->(c:Community)
                    where c.id = {$community['community_id']}
                    return n
                ", ['n']);

                foreach ($nodes as $node) {
                    $summaryText .= "{$node->properties['name']}; ";
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
                    $community['community_id'],
                    $community['community_level'],
                    $summaryText,
                    $context,
                );
                $alreadyIndexedCommunities[] = $community['community_id'] . '-' . $community['community_level'];
            } else {
                $childrenCommunities = $this->graphDB->queryMany("
                    match (parent:Community { level: {$community['community_level']}, id: {$community['community_id']} })<-[:CHILD_OF]-(child:Community)
                    return child
                ", ['child']);

                foreach ($childrenCommunities as $childCommunity) {
                    $summaryText .= "{$childCommunity->properties['name']}; ";
                    $context .= "{$childCommunity->properties['summary']}; ";
                }

                IndexGraphCommunity::dispatch(
                    $community['community_id'],
                    $community['community_level'],
                    $summaryText,
                    $context,
                );
                $alreadyIndexedCommunities[] = $community['community_id'] . '-' . $community['community_level'];
            }
        }
    }
}
