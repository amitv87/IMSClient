<?php
namespace imsclient\protocol\isakmp\eap;

class EAPAKAResponse extends EAPAKA
{
    protected static $_type = 23;
    protected static $_code = self::CODE_RESPONSE;
}
