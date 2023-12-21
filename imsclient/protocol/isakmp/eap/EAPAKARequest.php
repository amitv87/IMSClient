<?php
namespace imsclient\protocol\isakmp\eap;

class EAPAKARequest extends EAPAKA
{
    protected static $_type = 23;
    protected static $_code = self::CODE_REQUEST;
}
