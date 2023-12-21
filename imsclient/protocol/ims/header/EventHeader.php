<?php

namespace imsclient\protocol\ims\header;

class EventHeader extends GenericHeader
{
    static protected $_name = "Event";

    public $event;

    public function __construct($event)
    {
        $this->event = $event;
    }

    protected function generate()
    {
        $this->_value = $this->event;
    }

    protected function parse()
    {
        $this->event = $this->_value;
    }
}
