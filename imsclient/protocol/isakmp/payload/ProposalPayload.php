<?php

namespace imsclient\protocol\isakmp\payload;

use imsclient\exception\DataParseException;
use imsclient\datastruct\StringStream;

class ProposalPayload extends GenericPayload
{
    static protected $tag = 2;

    public $number = 0; // u8
    public $protocol_id = 0; // u8
    public $spi_size = 0; // u8
    private $num_transforms = 0; // u8

    public $spi; // u8[]
    /** @var TransformPayload[] */
    public $transforms = [];

    public function __construct($protocol_id, $spi = "", $transforms = [])
    {
        $this->protocol_id = $protocol_id;
        $this->spi = $spi;
        $this->transforms = $transforms;
    }

    public function parse()
    {
        $stream = new StringStream($this->_payload);

        $this->number = $stream->readU8();
        $this->protocol_id = $stream->readU8();
        $this->spi_size = $stream->readU8();
        $this->num_transforms = $stream->readU8();
        $this->spi = $stream->read($this->spi_size);
        $this->transforms = GenericPayload::parseChain(TransformPayload::getTag(), $stream);

        if (count($this->transforms) != $this->num_transforms)
            throw new DataParseException("Transform count mismatch, chained: " . count($this->transforms) . ", expected: " . $this->num_transforms);
    }

    protected function generate(): string
    {
        $this->spi_size = strlen($this->spi);
        $this->num_transforms = count($this->transforms);
        GenericPayload::prepareChain($this->transforms);
        $bytes = "";
        $bytes .= pack('C', $this->number);
        $bytes .= pack('C', $this->protocol_id);
        $bytes .= pack('C', $this->spi_size);
        $bytes .= pack('C', $this->num_transforms);
        $bytes .= $this->spi;
        foreach ($this->transforms as $transform) {
            $bytes .= $transform->pack();
        }
        return $bytes;
    }

    const PROTOCOL_ID_IKE = 1;
    const PROTOCOL_ID_ESP = 3;
}
