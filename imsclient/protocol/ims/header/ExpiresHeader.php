<?php

namespace imsclient\protocol\ims\header;

class ExpiresHeader extends GenericHeader
{
    static protected $_name = "Expires";

    public $expires;

    public function __construct($expires = '600000')
    {
        $this->expires = $expires;
    }

    protected function generate()
    {
        $this->_value = $this->expires;
    }
}
