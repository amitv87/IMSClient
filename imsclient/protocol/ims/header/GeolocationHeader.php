<?php

namespace imsclient\protocol\ims\header;

use imsclient\protocol\ims\uri\CidUri;

class GeolocationHeader extends GenericHeader
{
    static protected $_name = "Geolocation";

    /** @var CidUri */
    public $uri;

    public function __construct(CidUri $uri)
    {
        $this->uri = $uri;
    }

    protected function generate()
    {
        $this->_value = "<{$this->uri}>";
    }
}
