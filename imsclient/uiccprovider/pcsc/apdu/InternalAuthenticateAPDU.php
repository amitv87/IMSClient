<?php

namespace imsclient\uiccprovider\pcsc\apdu;

use imsclient\uiccprovider\pcsc\apdu\LcAPDURequest;

class InternalAuthenticateAPDU extends LcAPDURequest
{
    static protected $cla = 0x00;
    static protected $ins = 0x88;

    protected $p1 = 0;

    public function __construct($method, $data)
    {
        $this->p2 = $method;
        $this->data = $data;
    }

    const METHOD_3G = 0x81;
}
