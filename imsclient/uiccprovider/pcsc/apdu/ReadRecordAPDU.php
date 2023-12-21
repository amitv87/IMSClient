<?php

namespace imsclient\uiccprovider\pcsc\apdu;

use imsclient\uiccprovider\pcsc\apdu\LeAPDURequest;

class ReadRecordAPDU extends LeAPDURequest
{
    static protected $cla = 0x00;
    static protected $ins = 0xB2;

    public function __construct($offset, $length, $abs = true)
    {
        $this->p1 = $offset & 0xFF;
        if ($abs)
            $this->p2 = 0x04;
        $this->l = $length & 0xFF;
    }
}
