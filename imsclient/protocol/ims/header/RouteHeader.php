<?php

namespace imsclient\protocol\ims\header;

class RouteHeader extends GenericHeader
{
    static protected $_name = "Route";

    /** @var SIPUri[] */
    public $route = [];

    public function __construct($route = [])
    {
        if (is_array($route)) {
            $this->route = $route;
        } else {
            throw new \Exception("Route header must be an array");
        }
    }

    public function add($value)
    {
        $this->route[] = $value;
    }

    protected function generate()
    {
        $route = [];
        foreach ($this->route as $r) {
            $route[] = "<{$r->toString()}>";
        }
        $this->_value = implode(', ', $route);
    }
}
