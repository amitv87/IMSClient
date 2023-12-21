<?php

namespace imsclient\protocol\ims\header;

class UserAgentHeader extends GenericHeader
{
    static protected $_name = "User-Agent";

    private $useragent;

    public function __construct($useragent = 'IMSClient/1.0 Linux')
    {
        $this->useragent = $useragent;
    }

    protected function generate()
    {
        $this->_value = $this->useragent;
    }
}
