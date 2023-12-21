<?php

namespace imsclient\protocol\isakmp\payload;

use imsclient\datastruct\StringStream;

class TransformPayload extends GenericPayload
{
    static protected $tag = 3;

    public $type; // u8
    // reversed u8
    public $id; // u16be

    /** @var Attribute[] */
    public $attribute = [];

    public function __construct($type, $id, $attribute = [])
    {
        $this->type = $type;
        $this->id = $id;
        $this->attribute = $attribute;
    }

    protected function parse()
    {
        $stream = new StringStream($this->_payload);

        $this->type = $stream->readU8();
        $stream->readU8();
        $this->id = $stream->readU16be();

        $this->attribute = Attribute::unpack($stream->readAll());
    }

    protected function generate(): string
    {
        $bytes = "";
        $bytes .= pack('C', $this->type);
        $bytes .= pack('C', 0);
        $bytes .= pack('n', $this->id);
        foreach ($this->attribute as $value) {
            $bytes .= $value->pack();
        }
        return $bytes;
    }

    const TYPE_ENCR = 1;
    const TYPE_PRF = 2;
    const TYPE_INTEG = 3;
    const TYPE_DH = 4;
    const TYPE_ESN = 5;

    const ATTR_KEY_LENGTH = 14;
}
