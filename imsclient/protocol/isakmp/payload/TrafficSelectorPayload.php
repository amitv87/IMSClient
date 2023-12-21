<?php

namespace imsclient\protocol\isakmp\payload;

use imsclient\datastruct\StringStream;

class TrafficSelectorPayload extends GenericPayload
{
    private $count; // u8
    // reversed u8[3]
    /** @var TrafficSelector[] */
    public $selector = [];

    public function __construct($selector = [])
    {
        $this->selector = $selector;
    }

    protected function parse()
    {
        $stream = new StringStream($this->_payload);
        $this->count = $stream->readU8();
        $stream->read(3);
        $this->selector = TrafficSelector::unpack($stream->readAll());
    }

    protected function generate(): string
    {
        $this->count = count($this->selector);
        $bytes = "";
        $bytes .= pack('C', $this->count);
        $bytes .= pack('C', 0);
        $bytes .= pack('C', 0);
        $bytes .= pack('C', 0);
        foreach ($this->selector as $value) {
            $bytes .= $value->pack();
        }
        return $bytes;
    }
}
