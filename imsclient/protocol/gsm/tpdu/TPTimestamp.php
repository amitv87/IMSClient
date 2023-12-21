<?php

namespace imsclient\protocol\gsm\tpdu;

use imsclient\exception\DataParseException;
use imsclient\datastruct\StringStream;
use ReflectionClass;

class TPTimestamp
{
    private $value = [];

    public function __construct($unix_timestamp)
    {
        $this->value[] = str_pad(date('y', $unix_timestamp), 2, '0', STR_PAD_LEFT);
        $this->value[] = str_pad(date('m', $unix_timestamp), 2, '0', STR_PAD_LEFT);
        $this->value[] = str_pad(date('d', $unix_timestamp), 2, '0', STR_PAD_LEFT);
        $this->value[] = str_pad(date('H', $unix_timestamp), 2, '0', STR_PAD_LEFT);
        $this->value[] = str_pad(date('i', $unix_timestamp), 2, '0', STR_PAD_LEFT);
        $this->value[] = str_pad(date('s', $unix_timestamp), 2, '0', STR_PAD_LEFT);
    }

    public function toTimestamp()
    {
        return mktime(
            $this->value[3],
            $this->value[4],
            $this->value[5],
            $this->value[1],
            $this->value[2],
            $this->value[0]
        );
    }

    public function pack()
    {
        $bytes = "";
        foreach ($this->value as $value) {
            $value = strrev($value);
            $bytes .= hex2bin($value);
        }
        return $bytes . "\x00";
    }

    static public function unpack(StringStream $stream): self
    {
        $reflection = new ReflectionClass(self::class);
        $instance = $reflection->newInstanceWithoutConstructor();

        $bytes = $stream->read(7);
        if ($bytes == false || strlen($bytes) != 7) {
            throw new DataParseException("TPTimestamp unpack failed: bytes missing");
        }
        $bytes = str_split($bytes);
        foreach ($bytes as $byte) {
            $byte = bin2hex($byte);
            $byte = strrev($byte);
            $instance->value[] = $byte;
        }

        return $instance;
    }
}
