<?php

namespace imsclient\protocol\isakmp\payload;

use imsclient\datastruct\StringStream;

class ConfigurationPayload extends GenericPayload
{
    static protected $tag = 47;

    public $type; // u8
    // reversed u8[3]
    /** @var Attribute[] */
    public $attribute = [];

    public function __construct($type, $attribute = [])
    {
        $this->type = $type;
        $this->attribute = $attribute;
    }

    protected function parse()
    {
        $stream = new StringStream($this->_payload);
        $this->type = $stream->readU8();
        $stream->read(3);
        $this->attribute = Attribute::unpack($stream->readAll());
    }

    protected function generate(): string
    {
        $bytes = "";
        $bytes .= pack('C', $this->type);
        $bytes .= pack('C', 0);
        $bytes .= pack('C', 0);
        $bytes .= pack('C', 0);
        foreach ($this->attribute as $value) {
            $bytes .= $value->pack();
        }
        return $bytes;
    }

    const TYPE_CFG_REQUEST = 1;
    const TYPE_CFG_REPLY = 2;

    const ATTR_INTERNAL_IP4_ADDRESS = 1;
    const ATTR_INTERNAL_IP4_DNS = 3;
    const ATTR_INTERNAL_IP6_ADDRESS = 8;
    const ATTR_INTERNAL_IP6_DNS = 10;
    const ATTR_P_CSCF_IP4_ADDRESS = 20;
    const ATTR_P_CSCF_IP6_ADDRESS = 21;
}
