<?php

namespace imsclient\api;

use imsclient\exception\SocketException;
use imsclient\IMSClient;
use imsclient\log\Logger;
use imsclient\network\EventSocket;
use Throwable;

class APIServer
{
    private $path;

    /** @var IMSClient */
    private $server;
    /** @var APIService */
    private $service;
    /** @var EventSocket */
    private $socket;
    /** @var Eventsocket[] */
    private $clients = [];

    private $staged = [];

    public function __construct(IMSClient $server)
    {
        $this->server = $server;
        $this->service = new APIService($this);
        $dir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . "imsclient";
        @mkdir($dir, 0777, true);
        $this->path = $dir . DIRECTORY_SEPARATOR . $this->server->getIdentity()->imsi;
        @unlink($this->path);
    }

    public function run()
    {
        $this->socket = new EventSocket(EventSocket::UNIX, $this->path);
        chmod($this->path, 0777);
        $this->socket->onDataHandler = [$this, 'onData'];
        $this->socket->listen();
        Logger::success("Started on: {$this->path}");
    }

    public function shutdown()
    {
        if ($this->socket) {
            $this->socket->close();
        }
        unlink($this->path);
        foreach ($this->clients as $child) {
            $child['socket']->close();
        }
        if (count($this->staged) > 0) {
            file_put_contents("apiserver.staged", implode("", $this->staged), FILE_APPEND);
        }
    }

    private function notify(array $data)
    {
        $buf = json_encode($data) . "\n";
        $havesuccess = false;
        foreach ($this->clients as $id => $child) {
            try {
                $child['socket']->write($buf);
                $havesuccess = true;
            } catch (SocketException $se) {
                Logger::warning("Failed to notify child $id: " . $se->getMessage());
            }
        }
        if (!$havesuccess) {
            $this->staged[] = $buf;
            Logger::warning("Notify staged");
        }
    }

    public function onSMS($smsc, $from, $data)
    {
        $this->notify(['sms', $from, $data, $smsc]);
    }

    public function onData(EventSocket $socket)
    {
        $id = $socket->getId();
        if (!isset($this->clients[$id])) {
            $this->clients[$id] = ['buffer' => ''];
            foreach ($this->staged as $buf) {
                try {
                    $socket->write($buf);
                } catch (SocketException $se) {
                    Logger::warning("Failed to notify child $id: " . $se->getMessage());
                }
            }
            $this->staged = [];
        }
        $client = &$this->clients[$id];

        try {
            $data = $socket->read(1500);
            $client['buffer'] .= $data;
        } catch (SocketException $se) {
            unset($this->clients[$id]);
            $socket->close();
            return;
        }

        while (true) {
            $e = explode("\n", $client['buffer'], 2);
            if (count($e) < 2) {
                break;
            }
            $client['buffer'] = $e[1];
            $this->onRequest($socket, rtrim($e[0], "\r"));
        }
    }

    public function onRequest($socket, $request)
    {
        $request = json_decode($request, true);
        if (!is_array($request) || count($request) < 1) {
            return;
        }
        try {
            $method = array_shift($request);
            $response = call_user_func_array([$this->service, $method], $request);
        } catch (Throwable $t) {
            $response = $t;
        }
        $socket->write(json_encode($response));
    }
}
