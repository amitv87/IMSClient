<?php

namespace imsclient\uiccprovider\pcsc\apdu;

class SelectPathAPDU extends SelectAPDU
{
    protected $p1 = 0x08;
}
