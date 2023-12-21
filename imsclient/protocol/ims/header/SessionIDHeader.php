<?php

namespace imsclient\protocol\ims\header;

class SessionIDHeader extends GenericHeader
{
    static protected $_name = "Session-ID";

    private $sessionid;

    public function __construct($sessionid)
    {
        $this->sessionid = $sessionid;
    }

    protected function generate()
    {
        $this->_value = $this->sessionid;
    }
}
