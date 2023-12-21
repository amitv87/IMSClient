<?php

namespace imsclient\protocol\gsm\rp;

use imsclient\datastruct\StringStream;

abstract class RPData extends GenericRP
{
    /** @var RPAddress */
    public $originator;
    /** @var RPAddress */
    public $destination;
    public $userdata;

    public function __construct($reference, RPAddress $destination, $userdata)
    {
        parent::__construct($reference);
        $this->destination = $destination;
        $this->userdata = $userdata;
    }

    public function generate(): string
    {
        $bytes = "";
        if ($this->originator !== null) {
            $bytes .= $this->originator->pack();
        }else{
            $bytes .= pack('C', 0);
        }
        if($this->destination !== null){
            $bytes .= $this->destination->pack();
        }else{
            $bytes .= pack('C', 0);
        }
        $bytes .= pack('C', strlen($this->userdata));
        $bytes .= $this->userdata;
        return $bytes;
    }

    protected function parse(StringStream $stream)
    {
        $this->originator = RPAddress::unpack($stream);
        $this->destination = RPAddress::unpack($stream);
        $datalen = $stream->readU8();
        $this->userdata = $stream->read($datalen);
    }
}
