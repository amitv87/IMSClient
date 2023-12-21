<?php

namespace imsclient\protocol\ims\header;

abstract class BasicSecurityHeader extends GenericHeader
{
    /** @var BasicSecurityHeaderParam[] */
    public $params = [];

    public function add(BasicSecurityHeaderParam $param)
    {
        $this->params[] = $param;
        return $this;
    }

    protected function generate()
    {
        $this->_value = implode(',', $this->params);
    }

    protected function parse()
    {
        $this->params = array_map(function ($param) {
            return BasicSecurityHeaderParam::fromString($param);
        }, explode(',', $this->_value));
    }
}
