<?php

namespace imsclient\uiccprovider\pcsc\apdu;

class CloseLogicChannelAPDU extends LcAPDURequest
{
    static protected $cla = 0x00;
    static protected $ins = 0x70;

    protected $p1 = 0x80;
    protected $p2 = 0x00;

    public function __construct($id)
    {
        $this->p2 = $id;
    }
}
