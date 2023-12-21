<?php

namespace imsclient\protocol\ims\header;

class CallIDHeader extends GenericHeader
{
    static protected $_name = "Call-ID";

    public $value;

    public function __construct($value)
    {
        $this->value = $value;
    }

    protected function parse()
    {
        $this->value = $this->_value;
    }

    protected function generate()
    {
        $this->_value = $this->value;
    }
}
