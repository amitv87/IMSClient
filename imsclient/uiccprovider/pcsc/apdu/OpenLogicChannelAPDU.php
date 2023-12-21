<?php

namespace imsclient\uiccprovider\pcsc\apdu;

class OpenLogicChannelAPDU extends LeAPDURequest
{
    static protected $cla = 0x00;
    static protected $ins = 0x70;

    protected $p1 = 0x00;
    protected $p2 = 0x00;

    public function __construct()
    {
        parent::__construct(1);
    }
}
