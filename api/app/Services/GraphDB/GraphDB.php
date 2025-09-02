<?php

namespace App\Services\GraphDB;

use App\Services\GraphDB\Exceptions\NodeNotFoundException;
use App\Services\GraphDB\Exceptions\UnableToConnect;
use Bolt\Bolt;
use Bolt\connection\Socket;
use Bolt\protocol\AProtocol;
use Bolt\protocol\Response;
use Bolt\protocol\v5\structures\Node;
use Illuminate\Support\Arr;

abstract class GraphDB
{
    protected AProtocol $protocol;

    public abstract function createNode(string $label, array $attributes);
    public abstract function createNodeWithRelation(
        string $newNodeLabel,
        array $newNodeAttributes,
        string $relation,
        string $relatedNodeLabel,
        string $relatedNodeID,
    );
    abstract public function getNode(string $label, array $attributes): Node;

    public function __construct(array $config)
    {
        $conn = new Socket();
        $bolt = new Bolt($conn);
        $bolt->setProtocolVersions(5.2);
        $this->protocol = $bolt->build();
        $this->protocol->hello()->getResponse();
        /** @var Response $res */
        $res = $this->protocol->logon([
            'scheme' => $config['scheme'],
            'principal' => 'user',
            'credentials' => $config['password'],
        ])->getResponse();

        if ($res->signature->name !== 'SUCCESS') {
            throw new UnableToConnect('Unable to connect to GraphDB. config: ' . json_encode($config) . ', status: ' . json_encode($res->content));
        }
    }

    protected function arrToAttributeStr(array $attributes): string
    {
        $attributesStr = "";
        foreach ($attributes as $key => $value) {
            if (is_array($value)) {
                $value = json_encode($value);
            }
            $attributesStr .= "$key: \"$value\", ";
        }
        return rtrim($attributesStr, ", ");
    }

    protected function parseNode(array $rows): Node
    {
        $node = Arr::get($rows, '0.n');
        if (!$node) {
            throw new NodeNotFoundException('Unable to return node');
        }
        return $node;
    }
}
