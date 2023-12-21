<?php

namespace imsclient\protocol\ims\header;

class RequestDispositionHeader extends GenericHeader
{
    static protected $_name = "Request-Disposition";

    public function generate()
    {
        $this->_value = "no-fork";
    }
}
