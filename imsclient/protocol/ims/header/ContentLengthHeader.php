<?php

namespace imsclient\protocol\ims\header;

class ContentLengthHeader extends GenericHeader
{
    static protected $_name = "Content-Length";

    public $length;

    public function __construct($length = 0)
    {
        $this->length = $length;
    }

    protected function generate()
    {
        $this->_value = $this->length;
    }

    protected function parse()
    {
        $this->length = intval($this->_value);
    }

    public function getValue()
    {
        return $this->length;
    }
}
