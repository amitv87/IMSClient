<?php

namespace imsclient\network;

use imsclient\Identity;
use imsclient\log\Logger;

class IMSSocketPool
{
    /** @var Identity */
    private $idt;

    /** @var IMSSocket */
    private $initial;

    /** @var IMSSocket */
    private $client;

    /** @var IMSSocket */
    private $server;

    public function __construct($bindaddr)
    {
        Logger::info("Binding sockets on: [{$bindaddr}]");
        Logger::debug("Creating sockets...");
        $this->initial = new IMSSocket($bindaddr, [5060, 65534], true);
        $this->client = new IMSSocket($bindaddr, [10000, 65534], true);
        $this->server = new IMSSocket($bindaddr, [10000, 65534]);
        Logger::info("Self Port Initial: " . $this->initial->getPort() . ", Client: " . $this->client->getPort() . ", Server: " . $this->server->getPort());
    }

    public function start($server_addr)
    {
        Logger::info("Starting sockets...");
        $this->initial->connect($server_addr, 5060);
        Logger::success("Initial connected to: [{$server_addr}]:5060");
    }

    public function secure($server_addr, $server_port)
    {
        $this->server->listen();
        Logger::success("Server listening on: [{$this->server->getAddr()}]:{$this->server->getPort()}");
        Logger::debug("Connecting to secured endpoint: [{$server_addr}]:{$server_port}...");
        $this->client->connect($server_addr, $server_port);
        Logger::success("Connected to secured endpoint: [{$server_addr}]:{$server_port}");
    }

    public function initial()
    {
        return $this->initial;
    }

    public function client()
    {
        return $this->client;
    }

    public function server()
    {
        return $this->server;
    }

    public function closeInitial()
    {
        $this->initial->close();
        $this->initial = null;
    }
}
