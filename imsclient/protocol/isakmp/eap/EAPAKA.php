<?php

namespace imsclient\protocol\isakmp\eap;

use imsclient\datastruct\StringStream;

abstract class EAPAKA extends GenericEAP
{
    public $subtype; //u8
    // Reversed u16
    /** @var EAPAKAAttribute */
    public $attribute = [];

    public function __construct($id, $subtype, $attribute = [])
    {
        parent::__construct($id);
        $this->code = self::CODE_RESPONSE;
        $this->subtype = $subtype;
        $this->attribute = $attribute;
    }

    protected function generate(): string
    {
        $bytes = "";
        $bytes .= pack('C', $this->subtype);
        $bytes .= pack('n', 0);
        foreach ($this->attribute as $attr) {
            $bytes .= $attr->pack();
        }
        return $bytes;
    }

    protected function parse()
    {
        $stream = new StringStream($this->_payload);
        $this->subtype = $stream->readU8();
        $stream->readU16BE();
        $data = $stream->readAll();
        $this->attribute = EAPAKAAttribute::unpack($data);
    }

    const SUBTYPE_CHALLENGE = 1;
}
