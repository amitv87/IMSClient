<?php

namespace imsclient\protocol\ims\header;

class SupportedHeader extends GenericHeader
{
    static protected $_name = "Supported";

    private $supported = [];

    public function __construct($supported = ['100rel', 'path', 'replaces'])
    {
        if (is_array($supported)) {
            $this->supported = $supported;
        } else {
            throw new \Exception("Supported header must be an array");
        }
    }

    public function add($value)
    {
        $this->supported[] = $value;
    }

    protected function generate()
    {
        $this->_value = implode(',', $this->supported);
    }
}
