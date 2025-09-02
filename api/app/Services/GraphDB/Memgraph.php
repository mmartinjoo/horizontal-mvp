<?php

namespace App\Services\GraphDB;

use App\Services\GraphDB\Exceptions\InvalidCypherException;
use Bolt\protocol\v5\structures\Node;
use Illuminate\Support\Arr;
use Memgraph as MemgraphVendor;

class Memgraph extends GraphDB
{
    public function __construct(array $config)
    {
        parent::__construct($config);
        MemgraphVendor::$auth = ['scheme' => $config['scheme']];
    }

    public function createNode(string $label, array $attributes): Node
    {
        $attributesStr = "";
        foreach ($attributes as $key => $value) {
            $attributesStr .= "$key: \"$value\", ";
        }
        $attributesStr = rtrim($attributesStr, ", ");
        $rows = MemgraphVendor::query("create (n:Project { $attributesStr }) return n");
        $node = Arr::get($rows, '0.n');
        if (!$node) {
            throw new InvalidCypherException('Unable to return node');
        }
        return $node;
    }
}
