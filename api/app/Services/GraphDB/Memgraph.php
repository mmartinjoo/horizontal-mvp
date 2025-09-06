<?php

namespace App\Services\GraphDB;

use App\Services\GraphDB\Exceptions\InvalidCypherException;
use Bolt\protocol\v5\structures\Node;
use Illuminate\Support\Arr;
use Memgraph as MemgraphClient;

class Memgraph extends GraphDB
{
    public function __construct(array $config)
    {
        parent::__construct($config);
        MemgraphClient::$auth = ['scheme' => $config['scheme']];
    }

    public function createNode(string $label, array $attributes): ?Node
    {
        $attributesStr = $this->arrToAttributeStr($attributes);
        $rows = MemgraphClient::query("create (n:$label { $attributesStr }) return n;");
        return $this->parseNode($rows);
    }

    public function createNodeWithRelation(
        string $newNodeLabel,
        array $newNodeAttributes,
        string $relation,
        string $relatedNodeLabel,
        string $relatedNodeID,
        array $relationAttributes = [],
    ): ?Node {
        $newNodeId = $newNodeAttributes['id'];
        $upsertQuery = "merge (n:$newNodeLabel { id: \"$newNodeId\" })";
        $set = $this->arrToSetStyleStr($newNodeAttributes);
        $upsertQuery .= " $set";
        $upsertQuery .= " return n";

        $rows = MemgraphClient::query($upsertQuery);
        $node = Arr::get($rows, '0.n');
        if (!$node) {
            throw new InvalidCypherException('Unable to return node');
        }

        if (count($relationAttributes) === 0) {
            $rows = MemgraphClient::query("
                merge (r:$relatedNodeLabel { id: \"$relatedNodeID\" })
                with r
                match (n:$newNodeLabel { id: \"$newNodeId\" })
                merge (n)-[:$relation]->(r)
                return n;
            ");
            return $this->parseNode($rows);
        } else {
            $relationAttributesStr = $this->arrToAttributeStr($relationAttributes);
            $rows = MemgraphClient::query("
                merge (r:$relatedNodeLabel { id: \"$relatedNodeID\" })
                with r
                match (n:$newNodeLabel { id: \"$newNodeId\" })
                merge (n)-[:$relation { $relationAttributesStr }]->(r)
                return n;
            ");
            return $this->parseNode($rows);
        }
    }

    public function getNode(string $label, array $attributes): ?Node
    {
        $attributesStr = $this->arrToAttributeStr($attributes);
        $rows = MemgraphClient::query("match (n:$label $attributesStr) return n)");
        return $this->parseNode($rows);
    }

    public function query(string $query, string $nodeName = 'n'): ?Node
    {
        $rows = MemgraphClient::query($query);
        return $this->parseNode($rows, $nodeName);
    }

    public function queryMany(string $query, array $nodeNames = ['n']): array
    {
        $rows = MemgraphClient::query($query);
        return $this->parseNodes($rows, $nodeNames);
    }

    public function vectorSearch(string $indexName, array $embedding, int $n): array
    {
        $embeddingStr = json_encode($embedding);
        return MemgraphClient::query("
            CALL vector_search.search('$indexName', $n, $embeddingStr) YIELD * RETURN *;
        ");
    }

    public function addRelation(
        string $fromNodeLabel,
        string $fromNodeID,
        string $relation,
        string $toNodeLabel,
        string $toNodeID,
        array $relationAttributes = [],
    ): void
    {
        if (count($relationAttributes) === 0) {
            MemgraphClient::query("
                match (n1:$fromNodeLabel { id: \"$fromNodeID\" }), (n2:$toNodeLabel { id: \"$toNodeID\" })
                merge (n1)-[:$relation]->(n2)
            ");
        } else {
            $relationAttributesStr = $this->arrToAttributeStr($relationAttributes);
            MemgraphClient::query("
                match (n1:$fromNodeLabel { id: \"$fromNodeID\" }), (n2:$toNodeLabel { id: \"$toNodeID\" })
                merge (n1)-[:$relation { $relationAttributesStr }]->(n2)
            ");
        }
    }
}
