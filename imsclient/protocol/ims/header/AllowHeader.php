<?php

namespace imsclient\protocol\ims\header;

class AllowHeader extends GenericHeader
{
    static protected $_name = "Allow";

    public $allow = [];

    public function __construct($allow = ['ACK', 'BYE', 'CANCEL', 'INFO', 'INVITE', 'MESSAGE', 'NOTIFY', 'OPTIONS', 'PRACK', 'REFER', 'UPDATE'])
    {
        if (is_array($allow)) {
            $this->allow = $allow;
        } else {
            throw new \Exception("Allow header must be an array");
        }
    }

    public function add($value)
    {
        $this->allow[] = strtoupper($value);
    }

    protected function generate()
    {
        $this->_value = implode(',', $this->allow);
    }
}
