<?php

namespace imsclient\protocol\isakmp\payload;

use imsclient\protocol\isakmp\eap\GenericEAP;

class ExtensibleAuthenticationPayload extends GenericPayload
{
    static protected $tag = 48;

    /** @var GenericEAP */
    public $eap;

    public function __construct(GenericEAP $eap)
    {
        $this->eap = $eap;
    }

    protected function parse()
    {
        $this->eap = GenericEAP::unpack($this->_payload);
    }

    protected function generate(): string
    {
        return $this->eap->pack();
    }
}
