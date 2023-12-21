<?php

namespace imsclient\protocol\ims\uri;

class TelUri extends GenericUri
{
    static protected $_proto = "tel";

    public $number;

    public function __construct($number)
    {
        $this->number = $number;
    }

    public function generate()
    {
        $this->_value = $this->number;
    }

    public function parse()
    {
        $this->number = $this->_value;
    }
}
