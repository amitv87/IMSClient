<?php

namespace imsclient\protocol\ims\header;

class InReplyToHeader extends GenericHeader
{
    static protected $_name = "In-Reply-To";

    private $callid;

    public function __construct(CallIDHeader $callid)
    {
        $this->callid = $callid->value;
    }

    protected function generate()
    {
        $this->_value = $this->callid;
    }
}
