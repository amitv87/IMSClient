<?php

namespace imsclient\protocol\isakmp\payload;

class EncrypedAndAuthenticatedPayload extends GenericPayload
{
    static protected $tag = 46;

    public $raw;

    protected function generate(): string
    {
        return $this->raw;
    }
}
