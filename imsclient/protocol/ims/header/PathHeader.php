<?php

namespace imsclient\protocol\ims\header;

use imsclient\exception\DataParseException;
use imsclient\protocol\ims\uri\GenericUri;

class PathHeader extends GenericHeader
{
    static protected $_name = "Path";

    /** @var GenericUri */
    public $uri;

    protected function parse()
    {
        if (!preg_match('/<(.*?)>/', $this->_value, $matches)) {
            throw new DataParseException("Invalid Path header value: {$this->_value}");
        }
        $this->uri = GenericUri::fromString($matches[1]);
    }
}
