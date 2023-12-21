<?php

namespace imsclient\protocol\isakmp\payload;

class NoncePayload extends GenericPayload
{
    static protected $tag = 40;
    public $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    protected function parse()
    {
        $this->data = $this->_payload;
    }

    protected function generate(): string
    {
        return $this->data;
    }
}
