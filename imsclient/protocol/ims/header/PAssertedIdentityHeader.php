<?php

namespace imsclient\protocol\ims\header;

use imsclient\exception\DataParseException;
use imsclient\protocol\ims\uri\GenericUri;

class PAssertedIdentityHeader extends GenericHeader
{
    static protected $_name = "P-Asserted-Identity";

    /** @var GenericUri */
    public $uri;

    protected function parse()
    {
        $match = preg_match("/<(.*?)>/", $this->_value, $matches);
        if (!isset($matches[1])) {
            throw new DataParseException("P-Asserted-Identity parse error");
        }
        $this->uri = GenericUri::fromString($matches[1]);
    }
}
