<?php

namespace imsclient\protocol\isakmp\eap;

use imsclient\exception\DataParseException;
use imsclient\datastruct\StringStream;
use ReflectionClass;

class EAPAKAAttribute
{
    public $type; // u8
    public $length; // u8
    public $value;

    public function __construct($type, $value)
    {
        $this->type = $type;
        $this->value = $value;
    }

    public function pack()
    {
        $this->length = (strlen($this->value) + 2) / 4;
        if (ceil($this->length) != $this->length) {
            throw new DataParseException("Unaligned length");
        }
        $bytes = "";
        $bytes .= pack('C', $this->type);
        $bytes .= pack('C', $this->length);
        $bytes .= $this->value;
        return $bytes;
    }

    static public function unpack(string $data)
    {
        $ret = [];
        $stream = new StringStream($data);
        while ($stream->hasData()) {
            $reflection = new ReflectionClass(self::class);
            $instance = $reflection->newInstanceWithoutConstructor();
            $instance->type = $stream->readU8();
            $instance->length = $stream->readU8();
            $bytelength = ($instance->length * 4) - 2;
            $instance->value = $stream->read($bytelength);
            $ret[] = $instance;
        }
        return $ret;
    }

    const AT_RAND = 1;
    const AT_AUTN = 2;
    const AT_RES = 3;
    const AT_PADDING = 6;
    const AT_MAC = 11;
    const AT_IV = 129;
    const AT_ENCR_DATA = 130;
    const AT_NEXT_PSEUDONYM = 132;
    const AT_NEXT_REAUTH_ID = 133;
    const AT_CHECKCODE = 134;
}
