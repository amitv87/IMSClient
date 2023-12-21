<?php

namespace imsclient\network;

use Exception;
use imsclient\exception\FatalException;
use imsclient\exception\SocketException;
use ReflectionClass;

class EventSocket
{
    const TCP = 0;
    const UDP = 1;
    const UNIX = 2;

    /** @var self[] */
    static private $_class_map = [];

    private $parent;
    private $protocol;
    private $resource;
    private $resourceid;
    private $self_addr;
    private $self_port;
    private $peer_addr;
    private $peer_port;
    private $selectable;
    private $acceptable;
    private $reconnectable;
    private $mark = null;

    /** @var callable */
    public $onDataHandler;
    public $userdata;

    public function __construct($protocol, $addr = "::", $port = 0)
    {
        $socket = self::createSocket($protocol, $addr, $port);
        $this->protocol = $protocol;
        $this->resource = $socket;
        $this->resourceid = self::resource2id($socket);
        $this->selectable = false;
        $this->reconnectable = false;
        $this->mark = null;
        if (!@socket_bind($this->resource, $addr, $port)) {
            throw new SocketException('Unable to bind socket: ' . socket_strerror(socket_last_error($this->resource)));
        }
        socket_getsockname($this->resource, $this->self_addr, $this->self_port);
        if ($this->protocol === self::UDP) {
            $this->selectable = true;
        }

        self::$_class_map[$this->resourceid] = $this;
    }

    static private function createSocket($protocol, $addr)
    {
        $af_inet = null;
        $type = null;
        $soprotocol = null;

        switch ($protocol) {
            case self::TCP:
                $af_inet = strstr($addr, ":") ? AF_INET6 : AF_INET;
                $type = SOCK_STREAM;
                $soprotocol = SOL_TCP;
                break;
            case self::UDP:
                $af_inet = strstr($addr, ":") ? AF_INET6 : AF_INET;
                $type = SOCK_DGRAM;
                $soprotocol = SOL_UDP;
                break;
            case self::UNIX:
                $af_inet = AF_UNIX;
                $type = SOCK_STREAM;
                $soprotocol = 0;
                break;
            default:
                throw new Exception("Unknown protocol");
        }

        $socket = socket_create($af_inet, $type, $soprotocol);
        if (!$socket) {
            throw new SocketException('Unable to create socket: ' . socket_strerror(socket_last_error()));
        }
        return $socket;
    }

    public function setMark($mark)
    {
        if (!socket_set_option($this->resource, SOL_SOCKET, SO_MARK, $mark)) {
            throw new SocketException('Unable to set mark: ' . socket_strerror(socket_last_error($this->resource)));
        }
        $this->mark = $mark;
    }

    public function listen($backlog = 4)
    {
        if (!socket_listen($this->resource, $backlog)) {
            throw new SocketException('Unable to listen: ' . socket_strerror(socket_last_error($this->resource)));
        }
        $this->selectable = true;
        $this->acceptable = true;
    }

    public function connect($addr, $port)
    {
        if ($this->protocol === self::TCP) {
            socket_set_option($this->resource, SOL_SOCKET, SO_RCVTIMEO, array('sec' => 5, 'usec' => 0));
            socket_set_option($this->resource, SOL_SOCKET, SO_SNDTIMEO, array('sec' => 5, 'usec' => 0));
        }

        if (!socket_connect($this->resource, $addr, $port)) {
            throw new SocketException('Unable to connect: ' . socket_strerror(socket_last_error($this->resource)));
        }

        if ($this->protocol === self::TCP) {
            socket_set_option($this->resource, SOL_SOCKET, SO_KEEPALIVE, 1);
            socket_set_option($this->resource, SOL_TCP, TCP_KEEPIDLE, 10);
            socket_set_option($this->resource, SOL_TCP, TCP_KEEPINTVL, 5);
            socket_set_option($this->resource, SOL_TCP, TCP_KEEPCNT, 5);
        }

        if ($this->self_addr == "0.0.0.0" || $this->self_addr == "::") {
            socket_getsockname($this->resource, $this->self_addr, $this->self_port);
        }

        $this->peer_addr = $addr;
        $this->peer_port = $port;
        $this->selectable = true;
        $this->reconnectable = true;
    }

    public function accept(): self
    {
        $reflection = new ReflectionClass(self::class);
        $instance = $reflection->newInstanceWithoutConstructor();

        $socket = socket_accept($this->resource);
        if ($this->protocol === self::TCP) {
            socket_set_option($socket, SOL_SOCKET, SO_KEEPALIVE, 1);
            socket_set_option($socket, SOL_TCP, TCP_KEEPIDLE, 5);
            socket_set_option($socket, SOL_TCP, TCP_KEEPINTVL, 5);
            socket_set_option($socket, SOL_TCP, TCP_KEEPCNT, 5);
        }

        $instance->parent = $this;
        $instance->protocol = $this->protocol;
        $instance->resource = $socket;
        $instance->resourceid = self::resource2id($socket);
        socket_getsockname($socket, $instance->self_addr, $instance->self_port);
        socket_getpeername($socket, $instance->peer_addr, $instance->peer_port);
        $instance->selectable = true;
        $instance->acceptable = false;
        $instance->reconnectable = false;
        if ($this->mark !== null)
            $instance->setMark($this->mark);
        $instance->onDataHandler = $this->onDataHandler;

        self::$_class_map[$instance->resourceid] = $instance;
        return $instance;
    }

    public function reconnect()
    {
        if (!$this->reconnectable) {
            throw new Exception('Unable to reconnect');
        }
        $this->close();
        $socket = self::createSocket($this->protocol, $this->self_addr, $this->self_port);
        $this->resource = $socket;
        $this->resourceid = self::resource2id($socket);
        if ($this->mark !== null) {
            $this->setMark($this->mark);
        }
        $this->connect($this->peer_addr, $this->peer_port);
        self::$_class_map[$this->resourceid] = $this;
    }

    public function read($length)
    {
        $data = "";
        $ret = @socket_recv($this->resource, $data, $length, MSG_DONTWAIT);
        $lasterror = socket_last_error($this->resource);
        if ($ret === 0) {
            throw new SocketException('Possible remote closed: ' . socket_strerror($lasterror));
        }
        if ($ret === false && $lasterror !== SOCKET_EAGAIN) {
            throw new SocketException('Unable to read: ' . socket_strerror($lasterror));
        }
        return $data;
    }

    public function readLine($timeout = 5)
    {
        $buffer = "";
        while (1) {
            $read = [$this->resource];
            $write = null;
            $except = null;
            $ret = socket_select($read, $write, $except, $timeout);
            if ($ret === false) {
                throw new SocketException("Socket select error: " . socket_strerror(socket_last_error()));
            }
            if ($ret === 0) {
                throw new SocketException("Socket select timeout");
            }
            $chr = $this->read(1);
            if ($chr === "\n") {
                break;
            }
            $buffer .= $chr;
        }
        return $buffer;
    }

    public function write($data)
    {
        if (socket_send($this->resource, $data, strlen($data), 0) === false) {
            throw new SocketException('Unable to write: ' . socket_last_error($this->resource));
        }
    }

    public function writeLine($data)
    {
        return $this->write($data . "\n");
    }

    public function send($data)
    {
        return $this->write($data);
    }

    public function close()
    {
        if ($this->resource) {
            socket_shutdown($this->resource);
            socket_close($this->resource);
            unset(self::$_class_map[$this->resourceid]);
        }
        $this->resource = null;
        $this->userdata = null;
        $this->selectable = false;
    }

    public function isAcceptable()
    {
        return $this->acceptable;
    }

    public function isReconnectable()
    {
        return $this->reconnectable;
    }

    public function getResource()
    {
        return $this->resource;
    }

    public function getId()
    {
        return $this->resourceid;
    }

    public function getProtocol()
    {
        return $this->protocol;
    }

    public function getSelfAddr()
    {
        return $this->self_addr;
    }

    public function getSelfPort()
    {
        return $this->self_port;
    }

    public function getPeerAddr()
    {
        return $this->peer_addr;
    }

    public function getPeerPort()
    {
        return $this->peer_port;
    }

    public function getParent()
    {
        return $this->parent;
    }

    static public function getByResourceId($resourceid)
    {
        if (isset(self::$_class_map[$resourceid])) {
            return self::$_class_map[$resourceid];
        }
        return null;
    }

    static public function getSelectable()
    {
        $resources = [];
        foreach (self::$_class_map as $socket) {
            if ($socket->selectable && is_callable($socket->onDataHandler)) {
                $resources[] = $socket->getResource();
            }
        }
        return $resources;
    }

    static public function resource2id($resource)
    {
        $id = null;
        if (is_resource($resource)) {
            $id = intval($resource);
        } else {
            $id = spl_object_id($resource);
        }
        return $id;
    }

    static public function select($timeout = null)
    {
        $read = EventSocket::getSelectable();
        $write = null;
        $except = null;
        if (count($read) == 0) {
            gc_collect_cycles();
            sleep($timeout);
            return;
        }
        $ret = socket_select($read, $write, $except, $timeout);
        if ($ret === 0) {
            gc_collect_cycles();
            return;
        }
        if ($ret === false) {
            throw new SocketException("Socket select error: " . socket_strerror(socket_last_error()));
        }
        foreach ($read as $resource) {
            $id = EventSocket::resource2id($resource);
            $obj = EventSocket::getByResourceId($id);
            if ($obj === null) {
                throw new FatalException("Socket object not found, system out of sync");
            }
            if ($obj->isAcceptable()) {
                $obj->accept();
                continue;
            }
            if (is_callable($obj->onDataHandler)) {
                call_user_func($obj->onDataHandler, $obj);
            }
        }
    }
}
