<?php

namespace imsclient\protocol\ims\header;

use imsclient\protocol\ims\uri\GenericUri;

class FromHeader extends GenericHeader
{
    static protected $_name = "From";

    private $uri;
    private $tag;

    public function __construct(GenericUri $uri, $tag)
    {
        $this->uri = $uri;
        $this->tag = $tag;
    }

    protected function generate()
    {
        $this->_value = "<{$this->uri}>;tag={$this->tag}";
    }
}
