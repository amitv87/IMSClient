<?php

namespace imsclient\uiccprovider\pcsc\apdu;

use imsclient\uiccprovider\pcsc\apdu\LcAPDURequest;

abstract class SelectAPDU extends LcAPDURequest
{
    static protected $cla = 0x00;
    static protected $ins = 0xA4;

    public function __construct($file, $fci = false)
    {
        $this->data = $file;
        if ($fci) {
            $this->p2 = 0x04;
        } else {
            $this->p2 = 0x00;
        }
    }
}
