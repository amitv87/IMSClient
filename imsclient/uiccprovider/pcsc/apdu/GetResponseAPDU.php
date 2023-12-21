<?php

namespace imsclient\uiccprovider\pcsc\apdu;

use imsclient\uiccprovider\pcsc\apdu\LeAPDURequest;

class GetResponseAPDU extends LeAPDURequest
{
    static protected $cla = 0x00;
    static protected $ins = 0xC0;

    protected $p1 = 0x00;
    protected $p2 = 0x00;
}
