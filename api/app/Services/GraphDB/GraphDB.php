<?php

namespace App\Services\GraphDB;

use Bolt\Bolt;
use Bolt\connection\Socket;
use Bolt\protocol\AProtocol;

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
        $this->protocol->logon([
            'scheme' => 'none',
            'principal' => '',
            'credentials' => ''
        ])->getResponse();
    }

    public function run(string $chyper): void
    {
        $this->protocol->run($chyper);
    }
}
