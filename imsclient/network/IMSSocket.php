<?php

namespace imsclient\network;

use Exception;
use imsclient\exception\FatalException;
use imsclient\exception\SocketException;
use imsclient\IMSClient;
use imsclient\protocol\ims\PacketParser;

class IMSSocket
{
    private $addr;
    private $port;
    private $critical;

    /** @var EventSocket */
    private $udp_socket;
    /** @var EventSocket */
    private $tcp_socket;

    public function __construct($addr, array $portrange, $critical = false)
    {
        $this->addr = $addr;
        $this->critical = $critical;
        $this->udp_socket = null;
        $this->tcp_socket = null;

        if (count($portrange) != 2) {
            throw new Exception('Invalid port range');
        }

        for ($i = $portrange[0]; $i <= $portrange[1]; $i++) {
            try {
                $this->port = mt_rand($i, $portrange[1]);
                $this->udp_socket = new EventSocket(EventSocket::UDP, $addr, $this->port);
                $this->tcp_socket = new EventSocket(EventSocket::TCP, $addr, $this->port);
                break;
            } catch (Exception $e) {
                continue;
            }
        }

        if (!$this->udp_socket || !$this->tcp_socket) {
            throw new Exception('Unable to bind socket: ' . socket_strerror(socket_last_error()));
        }

        $this->udp_socket->userdata = new PacketParser();
        $this->tcp_socket->userdata = new PacketParser();

        $this->udp_socket->onDataHandler = [$this, 'onData'];
        $this->tcp_socket->onDataHandler = [$this, 'onData'];
    }

    public function onData(EventSocket $socket)
    {
        /** @var PacketParser $parser */
        try {
            $data = $socket->read(1500);
        } catch (SocketException $e) {
            $socket->close();
            if ($this->critical) {
                throw new FatalException("Critical socket error: {$e->getMessage()}");
            }
            return;
        }
        if (!($socket->userdata instanceof PacketParser)) {
            $socket->userdata = new PacketParser();
        }
        $parser = &$socket->userdata;
        $parser->read($data);
        $pkts = [];
        while ($pkt = $parser->getPacket()) {
            $pkt->setIncomeSocket($socket);
            $pkts[] = $pkt;
        }
        IMSClient::getInstance()->parsePacket($pkts);
    }

    public function setMark($mark)
    {
        $this->udp_socket->setMark($mark);
        $this->tcp_socket->setMark($mark);
    }

    public function listen($backlog = 4)
    {
        $this->tcp_socket->listen($backlog);
    }

    public function connect($addr, $port)
    {
        $this->udp_socket->connect($addr, $port);
        $this->tcp_socket->connect($addr, $port);
    }

    public function close()
    {
        $this->udp_socket->close();
        $this->tcp_socket->close();
    }

    public function udp()
    {
        return $this->udp_socket;
    }

    public function tcp()
    {
        return $this->tcp_socket;
    }

    public function getAddr()
    {
        return $this->addr;
    }

    public function getPort()
    {
        return $this->port;
    }
}
