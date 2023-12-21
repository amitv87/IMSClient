<?php

namespace imsclient\uiccprovider\pcsc\apdu;

class SelectNameAPDU extends SelectAPDU
{
    protected $p1 = 0x04;
}
