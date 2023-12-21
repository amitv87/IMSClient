<?php

namespace imsclient\protocol\isakmp\payload;

use imsclient\datastruct\StringStream;

abstract class IdentificationPayload extends GenericPayload
{
    public $type; //u8
    //reversed u8[3]
    public $data; // string

    public function __construct($type, $data)
    {
        $this->type = $type;
        $this->data = $data;
    }

    protected function generate(): string
    {
        $bytes = "";
        $bytes .= pack('C', $this->type);
        $bytes .= pack('C', 0);
        $bytes .= pack('C', 0);
        $bytes .= pack('C', 0);
        $bytes .= $this->data;
        return $bytes;
    }

    protected function parse()
    {
        $stream = new StringStream($this->_payload);

        $this->type = $stream->readU8();
        $stream->readU8();
        $stream->readU8();
        $stream->readU8();
        $this->data = $stream->readALL();
    }

    const TYPE_FQDN = 2;
    const TYPE_ID_RFC822_ADDR = 3;
}
