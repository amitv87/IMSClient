<?php

namespace imsclient\uiccprovider\pcsc\struct;

use imsclient\datastruct\StringStream;
use ReflectionClass;

abstract class TLV
{
    static protected $tag_reader;
    static protected $tag_packer;
    static protected $length_reader;
    static protected $length_packer;

    public $tag;
    protected $length;
    public $value;

    public function __construct($tag, string $value = "")
    {
        $this->tag = $tag;
        $this->value = $value;
    }

    static public function unpack(StringStream | string $stream): static
    {
        if (is_string($stream)) {
            $stream = new StringStream($stream);
        }
        $reflection = new ReflectionClass(static::class);
        $instance = $reflection->newInstanceWithoutConstructor();
        $instance->tag = $stream->{static::$tag_reader}();
        $instance->length = $stream->{static::$length_reader}();
        $instance->value = $stream->read($instance->length);
        return $instance;
    }

    public function pack(): string
    {
        $this->length = strlen($this->value);
        $buffer = "";
        $buffer .= pack(static::$tag_packer, $this->tag);
        $buffer .= pack(static::$length_packer, $this->length);
        $buffer .= $this->value;
        return $buffer;
    }

    public function __toString()
    {
        return $this->pack();
    }
}
