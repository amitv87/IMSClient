<?php

namespace imsclient\protocol\ims\header;

use imsclient\exception\DataParseException;
use ReflectionClass;

class StatusLine implements HeaderInterface
{
    public $version = '';
    public $code = '';
    public $reason = '';

    public function __construct($code, $reason, $version = "SIP/2.0")
    {
        $this->code = $code;
        $this->reason = $reason;
        $this->version = $version;
    }

    static public function getName()
    {
        return "Status-Line";
    }

    public function toString()
    {
        return "{$this->version} {$this->code} {$this->reason}";
    }

    static public function fromString($str): self
    {
        $reflection = new ReflectionClass(self::class);
        $instance = $reflection->newInstanceWithoutConstructor();

        $parts = explode(" ", $str, 3);
        if (count($parts) !== 3) {
            throw new DataParseException("Invalid Status-Line");
        }
        $instance->version = strtoupper($parts[0]);
        $instance->code = intval($parts[1]);
        $instance->reason = $parts[2];
        return $instance;
    }
}
