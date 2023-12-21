<?php

namespace imsclient\protocol\ims\header;

use imsclient\protocol\ims\uri\GenericUri;
use ReflectionClass;

class RequestLine implements HeaderInterface
{
    public $method = '';
    /** @var GenericUri */
    public $uri;
    public $version;

    public function __construct($method, GenericUri $uri, $version = "SIP/2.0")
    {
        $this->method = $method;
        $this->uri = $uri;
        $this->version = $version;
    }

    static public function getName()
    {
        return "Request-Line";
    }

    public function toString()
    {
        return "{$this->method} {$this->uri} {$this->version}";
    }

    static public function fromString($str): self
    {
        $reflection = new ReflectionClass(self::class);
        $instance = $reflection->newInstanceWithoutConstructor();

        $parts = explode(" ", $str);
        $instance->method = strtoupper($parts[0]);
        $instance->uri = GenericUri::fromString($parts[1]);
        $instance->version = $parts[2];
        return $instance;
    }
}
