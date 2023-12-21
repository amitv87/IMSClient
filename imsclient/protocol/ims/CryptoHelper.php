<?php

namespace imsclient\protocol\ims;

use imsclient\exception\DataParseException;

class CryptoHelper
{
    static public function integXFRMInfo($integ, $key)
    {
        switch ($integ) {
            case self::ALG_HMAC_MD5_96:
                return "auth-trunc \"hmac(md5)\" \"0x" . bin2hex($key) . "\" 96";
            case self::ALG_HMAC_SHA1_96:
                return  "auth-trunc \"hmac(sha1)\" \"0x" . bin2hex($key) . "\" 96";
        }
        throw new DataParseException("INTEG not impl");
    }

    static public function encrXFRMInfo($encr, $key = "")
    {
        switch ($encr) {
            case self::EALG_NULL:
                return "enc \"cipher_null\" \"\"";
        }
        throw new DataParseException("ENCR not impl");
    }

    const ALG_HMAC_MD5_96 = 'hmac-md5-96';
    const ALG_HMAC_SHA1_96 = 'hmac-sha-1-96';

    const EALG_NULL = 'null';
    const EALG_AES_CBC = 'aes-cbc';
}
