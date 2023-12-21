<?php

namespace imsclient\protocol\ims\header;

class PLastAccessNetworkInfo extends GenericHeader
{
    static protected $_name = "P-Last-Access-Network-Info";

    public function generate()
    {
        $this->_value = "IEEE-802.11;i-wlan-node-id=ffffffffffff";
    }
}
