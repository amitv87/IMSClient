<?php

namespace imsclient\protocol\isakmp\payload;

use imsclient\datastruct\StringStream;

class NotifyPayload extends GenericPayload
{
    static protected $tag = 41;
    public $protocol_id; // u8
    private $spi_size; // u8
    public $notify_type; // u16be
    public $spi; // string
    public $data; // string

    protected function parse()
    {
        $stream = new StringStream($this->_payload);
        $this->protocol_id = $stream->readU8();
        $this->spi_size = $stream->readU8();
        $this->notify_type = $stream->readU16BE();
        $this->spi = $stream->read($this->spi_size);
        $this->data = $stream->readAll();
    }

    protected function generate(): string
    {
        $this->spi_size = strlen($this->spi);
        $bytes = "";
        $bytes .= pack('C', $this->protocol_id);
        $bytes .= pack('C', $this->spi_size);
        $bytes .= pack('n', $this->notify_type);
        $bytes .= $this->spi;
        $bytes .= $this->data;
        return $bytes;
    }

    const PROTOCOL_ID_NONE = 0;

    const TYPE_NO_PROPOSAL_CHOSEN = 14; // ERROR TYPE
    const TYPE_NAT_DETECTION_SOURCE_IP = 16388;
    const TYPE_NAT_DETECTION_DESTINATION_IP = 16389;
    const TYPE_EAP_ONLY_AUTHENTICATION = 16417;
    const TYPE_DEVICE_IDENTITY = 41101;
}
