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

    public function createNode(string $label, array $attributes): Node
    {
        $attributesStr = $this->arrToAttributeStr($attributes);
        $rows = MemgraphClient::query("create (n:$label { $attributesStr }) return n;");
        $node = Arr::get($rows, '0.n');
        if (!$node) {
            throw new InvalidCypherException('Unable to return node');
        }
        return $node;
    }

    public function createNodeWithRelation(
        string $newNodeLabel,
        array $newNodeAttributes,
        string $relation,
        string $relatedNodeLabel,
        string $relatedNodeID,
    ) {
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

        $rows = MemgraphClient::query("
            merge (r:$relatedNodeLabel { id: \"$relatedNodeID\" })
            with r
            match (n:$newNodeLabel { id: \"$newNodeId\" })
            merge (n)-[:$relation]->(r)
            return n;
        ");
        return $this->parseNode($rows);
    }

    public function getNode(string $label, array $attributes): Node
    {
        $attributesStr = $this->arrToAttributeStr($attributes);
        $rows = MemgraphClient::query("match (n:$label $attributesStr) return n)");
        return $this->parseNode($rows);
    }

    public function query(string $query)
    {
        MemgraphClient::query($query);
    }
}
