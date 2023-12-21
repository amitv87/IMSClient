<?php

namespace imsclient\uiccprovider\pcsc\apdu;

use Exception;

abstract class LeAPDURequest extends APDURequest
{
    public function __construct($reqLen)
    {
        $this->l = $reqLen & 0xFF;
        $this->data = null;
    }

    protected function _generate()
    {
        if ($this->data !== null) {
            throw new Exception("LeAPDURequest does not support data");
        }
    }
}
