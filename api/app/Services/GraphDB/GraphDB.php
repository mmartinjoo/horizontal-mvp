<?php

namespace App\Services\GraphDB;

use App\Services\GraphDB\Exceptions\UnableToConnect;
use Bolt\Bolt;
use Bolt\connection\Socket;
use Bolt\protocol\AProtocol;
use Bolt\protocol\Response;

abstract class GraphDB
{
    private AProtocol $protocol;
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

    public function run(string $chyper): void
    {
        $this->protocol->run($chyper);
    }
}
