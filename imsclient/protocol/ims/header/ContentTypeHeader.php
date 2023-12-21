<?php

namespace imsclient\protocol\ims\header;

class ContentTypeHeader extends GenericHeader
{
    static protected $_name = "Content-Type";

    public $type;

    public function __construct($type)
    {
        $this->type = $type;
    }

    protected function generate()
    {
        $this->_value = $this->type;
    }
}
