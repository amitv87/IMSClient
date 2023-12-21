<?php

namespace imsclient\protocol\ims\header;

class RequireHeader extends GenericHeader
{
    static protected $_name = "Require";

    private $require = [];

    public function __construct($require = ['sec-agree'])
    {
        if (is_array($require)) {
            $this->require = $require;
        } else {
            throw new \Exception("Require header must be an array");
        }
    }

    public function add($value)
    {
        $this->require[] = $value;
    }

    protected function generate()
    {
        $this->_value = implode(',', $this->require);
    }
}
