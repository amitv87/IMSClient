<?php

namespace imsclient\protocol\gsm\rp;

use imsclient\exception\DataParseException;
use imsclient\datastruct\StringStream;

abstract class RPAck extends GenericRP
{
    public $element_id = 0x41;

    public $user_data;

    public function __construct(RPData $rpdata)
    {
        $this->reference = $rpdata->reference;
    }

    public function generate(): string
    {
        $bytes = "";
        if ($this->user_data) {
            $bytes .= pack('C', $this->element_id);
            $bytes .= pack('C', strlen($this->user_data));
            $bytes .= $this->user_data;
        }
        return $bytes;
    }

    protected function parse(StringStream $stream)
    {
        try {
            $IEI = $stream->readU8();
        } catch (DataParseException $e) {
            $this->user_data = null;
            return;
        }
        if ($IEI != 0x41) {
            throw new DataParseException("unknown IEI: " . bin2hex($IEI));
        }
        $datalen = $stream->readU8();
        $this->user_data = $stream->read($datalen);
    }
}
