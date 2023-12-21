<?php

namespace imsclient\protocol\ims\header;

use imsclient\exception\DataParseException;

class WWWAuthenticateHeader extends AuthorizationHeader
{
    static protected $_name = "WWW-Authenticate";

    protected function generate()
    {
        throw new \Exception("generate unimpl");
    }

    protected function parse()
    {
        // Digest realm="ims.mnc240.mcc310.3gppnetwork.org",nonce="96uLRd3iedcw8VCSm+rKVQvd07MVAwAA73GPsbPIFRw=",algorithm=AKAv1-MD5
        $exp = explode(" ", $this->_value, 2);
        if (count($exp) != 2)
            throw new DataParseException("Invalid WWW-Authenticate header");
        if ($exp[0] != "Digest")
            throw new DataParseException("Invalid WWW-Authenticate header");
        $exp = explode(",", $exp[1]);
        foreach ($exp as $e) {
            $e = trim($e);
            $e = explode("=", $e, 2);
            if (count($e) != 2)
                throw new DataParseException("Invalid WWW-Authenticate header");
            $this->{$e[0]} = trim($e[1], "\"");
        }
    }
}
