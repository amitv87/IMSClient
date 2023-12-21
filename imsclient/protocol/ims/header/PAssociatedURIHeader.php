<?php

namespace imsclient\protocol\ims\header;

use imsclient\protocol\ims\uri\GenericUri;

class PAssociatedURIHeader extends GenericHeader
{
    static protected $_name = "P-Associated-Uri";

    /** @var GenericHeader */
    public $uri;

    public function parse()
    {
        $match = preg_match("/<(.*?)>/", $this->_value, $matches);
        if (!$match || !isset($matches[1])) {
            throw new \Exception("P-Associated-Uri parse error");
        }
        $this->uri = GenericUri::fromString($matches[1]);
    }
}
