<?php

namespace imsclient\protocol\ims\header;

use imsclient\protocol\ims\uri\GenericUri;

class PPreferredIdentityHeader extends GenericHeader
{
    static protected $_name = "P-Preferred-Identity";

    /** @var GenericUri */
    public $id;

    public function __construct(GenericUri $id)
    {
        $this->id = $id;
    }

    public function generate()
    {
        $this->_value = $this->id->toString();
    }
}
