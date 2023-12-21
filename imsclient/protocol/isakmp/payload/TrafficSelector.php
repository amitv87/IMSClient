<?php

namespace imsclient\protocol\isakmp\payload;

use imsclient\datastruct\StringStream;

class TrafficSelector
{
    public $type; // u8
    public $protocol_id; // u8
    private $selector_length; // u16be
    public $payload;

    public function __construct($type = 0, $protocol_id = 0, $payload = "")
    {
        $this->type = $type;
        $this->protocol_id = $protocol_id;
        $this->payload = $payload;
    }

    public function pack()
    {
        $this->selector_length = strlen($this->payload) + 4;
        $bytes = "";
        $bytes .= pack('C', $this->type);
        $bytes .= pack('C', $this->protocol_id);
        $bytes .= pack('n', $this->selector_length);
        $bytes .= $this->payload;
        return $bytes;
    }

    static public function unpack($data)
    {
        $stream = new StringStream($data);
        $result = [];
        while ($stream->hasData()) {
            $ts = new TrafficSelector();
            $ts->type = $stream->readU8();
            $ts->protocol_id = $stream->readU8();
            $ts->selector_length = $stream->readU16BE();
            $ts->payload = $stream->read($ts->selector_length - 4);
            $result[] = $ts;
        }
        return $result;
    }

    const TS_IPV4_ADDR_RANGE = 7;
    const TS_IPV6_ADDR_RANGE = 8;
}
