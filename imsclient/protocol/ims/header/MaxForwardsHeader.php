<?php

namespace imsclient\protocol\ims\header;

class MaxForwardsHeader extends GenericHeader
{
    static protected $_name = "Max-Forwards";

    private $maxforwards;

    public function __construct($maxforwards = '70')
    {
        $this->maxforwards = $maxforwards;
    }

    protected function generate()
    {
        $this->_value = $this->maxforwards;
    }
}
