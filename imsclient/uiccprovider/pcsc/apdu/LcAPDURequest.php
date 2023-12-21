<?php

namespace imsclient\uiccprovider\pcsc\apdu;

abstract class LcAPDURequest extends APDURequest
{
    protected function _generate()
    {
        $this->l = strlen($this->data);
    }
}
