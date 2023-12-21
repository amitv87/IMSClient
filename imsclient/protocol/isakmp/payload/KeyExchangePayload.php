<?php

namespace imsclient\protocol\isakmp\payload;

use imsclient\datastruct\StringStream;

class KeyExchangePayload extends GenericPayload
{
    static protected $tag = 34;
    public $dh_group; // u16be
    //reversed u16be
    public $data;

    public function __construct($dh_group, $data)
    {
        $this->dh_group = $dh_group;
        $this->data = $data;
    }

    protected function parse()
    {
        $stream = new StringStream($this->_payload);

        $this->dh_group = $stream->readU16be();
        $stream->readU16be();
        $this->data = $stream->readALL();
    }

    protected function generate(): string
    {
        $bytes = "";
        $bytes .= pack('n', $this->dh_group);
        $bytes .= pack('n', 0);
        $bytes .= $this->data;
        return $bytes;
    }
}
