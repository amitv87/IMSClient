<?php

namespace imsclient\protocol\ims\header;

use imsclient\protocol\ims\uri\GenericUri;

class ToHeader extends GenericHeader
{
    static protected $_name = "To";

    /** @var GenericUri */
    public $uri;

    public $tag;

    public function __construct(GenericUri $uri, $tag = null)
    {
        $this->uri = $uri;
        $this->tag = $tag;
    }

    protected function generate()
    {
        $this->_value = "<$this->uri>";
        if ($this->tag) {
            $this->_value .= ";tag={$this->tag}";
        }
    }
}
