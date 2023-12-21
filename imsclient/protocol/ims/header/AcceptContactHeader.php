<?php

namespace imsclient\protocol\ims\header;

class AcceptContactHeader extends GenericHeader
{
    static protected $_name = "Accept-Contact";

    public $value;

    public function __construct($value)
    {
        $this->value = $value;
    }

    protected function generate()
    {
        $this->_value = $this->value;
    }
}
