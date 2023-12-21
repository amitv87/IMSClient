<?php

namespace imsclient\protocol\ims\header;

use imsclient\protocol\ims\uri\GenericUri;
use imsclient\protocol\ims\uri\SIPUri;

class AuthorizationHeader extends GenericHeader
{
    static protected $_name = "Authorization";

    /** @var GenericUri */
    public $uri;
    /** @var SIPUri */
    public $useruri;

    public $nonce;
    public $response;

    public function __construct(GenericUri $uri, SIPUri $useruri, $nonce = null, $response = null)
    {
        $this->uri = $uri;
        $this->useruri = $useruri;
        $this->nonce = $nonce;
        $this->response = $response;
    }

    protected function generate()
    {
        $username = $this->useruri->getValue();
        $realm = $this->useruri->host;
        $this->_value = "Digest nonce=\"{$this->nonce}\",uri=\"{$this->uri}\",response=\"{$this->response}\",username=\"{$username}\",algorithm=AKAv1-MD5,realm=\"{$realm}\"";
    }
}
