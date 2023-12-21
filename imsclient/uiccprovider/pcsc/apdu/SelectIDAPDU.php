<?php

namespace imsclient\uiccprovider\pcsc\apdu;

class SelectIDAPDU extends SelectAPDU
{
    protected $p1 = 0x00;
}
