<?php

namespace imsclient\protocol\isakmp\payload;

use imsclient\datastruct\StringStream;
use ReflectionClass;

class Attribute
{
    public $format;
    public $type;
    public $length;
    public $value;

    public function __construct($format, $type, $value = "")
    {
        $this->format = $format;
        $this->type = $type;
        $this->value = $value;
    }

    public function pack()
    {
        $this->length = strlen($this->value);
        $bytes = "";
        $bytes .= pack('n', ($this->format << 15) | $this->type);
        switch ($this->format) {
            case static::FORMAT_TLV:
                $bytes .= pack('n', $this->length);
                $bytes .= $this->value;
                break;
            case static::FORMAT_TV:
                $bytes .= pack('n', $this->value);
                break;
        }
        return $bytes;
    }

    static public function unpack(string $data)
    {
        $ret = [];
        $stream = new StringStream($data);
        while ($stream->hasData()) {
            $reflection = new ReflectionClass(self::class);
            $instance = $reflection->newInstanceWithoutConstructor();
            $byte = $stream->readU16BE();
            $instance->format = $byte >> 15;
            $instance->type = $byte & 0x7fff;
            switch ($instance->format) {
                case static::FORMAT_TLV:
                    $instance->length = $stream->readU16BE();
                    $instance->value = $stream->read($instance->length);
                    break;
                case static::FORMAT_TV:
                    $instance->value = $stream->readU16BE();
                    break;
            }
            $ret[] = $instance;
        }
        return $ret;
    }

    const FORMAT_TLV = 0;
    const FORMAT_TV = 1;
}
