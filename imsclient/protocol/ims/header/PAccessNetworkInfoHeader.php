<?php

namespace imsclient\protocol\ims\header;

class PAccessNetworkInfoHeader extends GenericHeader
{
    static protected $_name = "P-Access-Network-Info";

    public function generate()
    {
        $this->_value = "IEEE-802.11;i-wlan-node-id=ffffffffffff";
    }
}
