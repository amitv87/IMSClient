<?php

namespace imsclient\protocol\ims\header;

class GeolocationRoutingHeader extends GenericHeader
{
    static protected $_name = "Geolocation-Routing";

    private $value;

    public function __construct($value = true)
    {
        $this->value = $value;
    }

    protected function generate()
    {
        $this->_value = $this->value ? 'yes' : 'no';
    }
}
