<?php

namespace imsclient\uiccprovider\pcsc\apdu;

use imsclient\uiccprovider\pcsc\apdu\LeAPDURequest;

class ReadBinaryAPDU extends LeAPDURequest
{
    static protected $cla = 0x00;
    static protected $ins = 0xB0;

    public function __construct($length, $offset = 0)
    {
        $this->p1 = ($offset >> 8) & 0xFF;
        $this->p2 = $offset & 0xFF;
        $this->l = $length & 0xFF;
    }
}
