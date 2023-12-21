<?php

namespace imsclient\protocol\isakmp;

use Exception;
use imsclient\exception\DataParseException;
use imsclient\datastruct\StringStream;

class CryptoHelper
{
    private $dh;
    public readonly string $spi_initiator;
    public readonly string $spi_responder;
    private $nonce_initiator;
    private $nonce_responder;
    private $prf;
    private $encr;
    private $encr_length;
    private $integ;

    public readonly string $sk_d;
    public readonly string $sk_ai;
    public readonly string $sk_ar;
    public readonly string $sk_ei;
    public readonly string $sk_er;
    public readonly string $sk_pi;
    public readonly string $sk_pr;

    public readonly string $eap_res;
    public readonly string $eap_ik;
    public readonly string $eap_ck;
    public readonly string $eap_mk;
    public readonly string $eap_encr;
    public readonly string $eap_aut;
    public readonly string $eap_msk;
    public readonly string $eap_emsk;

    public function setNonce($initiator, $responder)
    {
        $this->nonce_initiator = $initiator;
        $this->nonce_responder = $responder;
    }

    public function setSPI($initiator, $responder)
    {
        $this->spi_initiator = $initiator;
        $this->spi_responder = $responder;
    }

    public function initDH($bitlength)
    {
    }

    public function getDHPublicKey()
    {
        $details = openssl_pkey_get_details($this->dh);
        return $details['dh']['pub_key'];
    }

    public function computeDHKey($peerdh)
    {
        return openssl_dh_compute_key($peerdh, $this->dh);
    }

    public function setCryptoDH($dh)
    {
        $this->dh = openssl_pkey_new([
            'private_key_type' => OPENSSL_KEYTYPE_DH,
            'dh' => [
                'p' => hex2bin(self::getMODP($dh)),
                'g' => hex2bin("02"),
            ],
        ]);
    }

    public function setCryptoMode($prf, $integ, $encr, $encr_length)
    {
        $this->prf  = $prf;
        $this->integ = $integ;
        $this->encr = $encr;
        $this->encr_length = $encr_length;
    }

    public function generateIKESecret($peerdh)
    {
        $DH_KEY = $this->computeDHKey($peerdh);
        $NONCE = $this->nonce_initiator . $this->nonce_responder;
        $SKEYSEED = $this->prfHmac($DH_KEY, $NONCE);
        $STREAM = $NONCE . pack('J', $this->spi_initiator) . pack('J', $this->spi_responder);
        $KET_LENGTH_TOTAL = (3 * $this->prfLength()) + (2 * $this->integKeyLength()) + (2 * $this->encrKeyLength());
        $KEY_STREAM = $this->prfPlus($SKEYSEED, $STREAM, $KET_LENGTH_TOTAL);
        $KEY_STREAM = new StringStream($KEY_STREAM);
        $this->sk_d = $KEY_STREAM->read($this->prfLength());
        $this->sk_ai = $KEY_STREAM->read($this->integKeyLength());
        $this->sk_ar = $KEY_STREAM->read($this->integKeyLength());
        $this->sk_ei = $KEY_STREAM->read($this->encrKeyLength());
        $this->sk_er = $KEY_STREAM->read($this->encrKeyLength());
        $this->sk_pi = $KEY_STREAM->read($this->prfLength());
        $this->sk_pr = $KEY_STREAM->read($this->prfLength());

        // Logger::debug("DH_KEY: " . bin2hex($DH_KEY));
        // Logger::debug("NONCE: " . bin2hex($NONCE));
        // Logger::debug("SKEYSEED: " . bin2hex($SKEYSEED));
        // Logger::debug("STREAM: " . bin2hex($STREAM));
        // Logger::debug("sk_ d: " . bin2hex($this->sk_d));
        // Logger::debug("sk_ai: " . bin2hex($this->sk_ai));
        // Logger::debug("sk_ar: " . bin2hex($this->sk_ar));
        // Logger::debug("sk_ei: " . bin2hex($this->sk_ei));
        // Logger::debug("sk_er: " . bin2hex($this->sk_er));
        // Logger::debug("sk_pi: " . bin2hex($this->sk_pi));
        // Logger::debug("sk_pr: " . bin2hex($this->sk_pr));
    }

    public function generateESPSecret($encr_length, $integ)
    {
        $NONCE = $this->nonce_initiator . $this->nonce_responder;
        $ESP_KET_LENGTH_TOTAL = (2 * self::integKeyLengthInfo($integ)) + (2 * self::encrKeyLengthInfo($encr_length));
        $ESP_KEY_STREAM = $this->prfPlus($this->sk_d, $NONCE, $ESP_KET_LENGTH_TOTAL);
        $ESP_KEY_STREAM = new StringStream($ESP_KEY_STREAM);
        return [
            'sk_ei' => $ESP_KEY_STREAM->read(self::encrKeyLengthInfo($encr_length)),
            'sk_ai' => $ESP_KEY_STREAM->read(self::integKeyLengthInfo($integ)),
            'sk_er' => $ESP_KEY_STREAM->read(self::encrKeyLengthInfo($encr_length)),
            'sk_ar' => $ESP_KEY_STREAM->read(self::integKeyLengthInfo($integ)),
        ];
    }

    public function prfPlus($KEY, $STREAM, $SIZE)
    {
        $ret = "";
        $lastsegment = "";
        for ($i = 1; strlen($ret) < $SIZE; $i++) {
            $lastsegment = $this->prfHmac($lastsegment . $STREAM . pack('C', $i), $KEY);
            $ret .= $lastsegment;
        }
        return $ret;
    }

    public function encrXFRM($key)
    {
        return self::encrXFRMInfo($this->encr, $this->encr_length, $key);
    }

    public function encrNameWiresharkIKE()
    {
        return self::encrNameWiresharkIKEInfo($this->encr, $this->encr_length);
    }

    public function encrNameWiresharkESP()
    {
        return self::encrNameWiresharkESPInfo($this->encr, $this->encr_length);
    }

    private function encrGetOpenSSLName()
    {
        return self::encrGetOpenSSLNameInfo($this->encr, $this->encr_length);
    }

    public function encrIVLength()
    {
        return self::encrIVLengthInfo($this->encr);
    }

    public function encrKeyLength()
    {
        return self::encrKeyLengthInfo($this->encr_length);
    }

    public function encrBlockLength()
    {
        return $this->encrKeyLength();
    }

    public function encrEncrypt($data, $key, $iv)
    {
        return openssl_encrypt($data, $this->encrGetOpenSSLName(), $key, OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING, $iv);
    }

    public function encrDecrypt($data, $key, $iv)
    {
        return openssl_decrypt($data, $this->encrGetOpenSSLName(), $key, OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING, $iv);
    }

    public function integXFRM($key)
    {
        return self::integXFRMInfo($this->integ, $key);
    }

    public function integNameWiresharkIKE()
    {
        return self::integNameWiresharkIKEInfo($this->integ);
    }

    public function integNameWiresharkESP()
    {
        return self::integNameWiresharkESPInfo($this->integ);
    }

    public function integKeyLength()
    {
        return self::integKeyLengthInfo($this->integ);
    }

    public function integLength()
    {
        return self::integLengthInfo($this->integ);
    }

    public function integHmac($data, $key)
    {
        $hname = "";
        switch ($this->integ) {
            case self::AUTH_HMAC_MD5_96:
                $hname = "md5";
                break;
            case self::AUTH_HMAC_SHA1_96:
                $hname = "sha1";
                break;
            case self::AUTH_HMAC_SHA2_256_128:
                $hname = "sha256";
                break;
            default:
                throw new DataParseException("INTEG not impl");
        }
        return substr(hash_hmac($hname, $data, $key, true), 0, $this->integLength());
    }

    public function prfLength()
    {
        switch ($this->prf) {
            case self::PRF_HMAC_MD5:
                return 16;
            case self::PRF_HMAC_SHA1:
                return 20;
            case self::PRF_HMAC_SHA2_256:
                return 32;
            default:
                throw new DataParseException("PRF not impl");
        }
    }

    public function prfHmac($data, $key)
    {
        $hname = "";
        switch ($this->prf) {
            case self::PRF_HMAC_MD5:
                $hname = "md5";
                break;
            case self::PRF_HMAC_SHA1:
                $hname = "sha1";
                break;
            case self::PRF_HMAC_SHA2_256:
                $hname = "sha256";
                break;
            default:
                throw new DataParseException("PRF not impl");
        }
        return hash_hmac($hname, $data, $key, true);
    }

    public function eap($identity, $res, $ik, $ck)
    {
        $keys = self::eap_keys($identity, $ik, $ck);
        $this->eap_res = $res;
        $this->eap_mk = $keys['mk'];
        $this->eap_encr = $keys['encr'];
        $this->eap_aut = $keys['aut'];
        $this->eap_msk = $keys['msk'];
        $this->eap_emsk = $keys['emsk'];
    }

    public function eap_decrypt($iv, $data)
    {
        switch (strlen($iv)) {
            case 16:
                return openssl_decrypt($data, "aes-128-cbc", $this->eap_encr, OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING, $iv);
            default:
                throw new DataParseException("EAP IV not impl");
        }
    }

    static private function eap_keys($identity, $ik, $ck)
    {
        $mk = hash('sha1', $identity . $ik . $ck, true);

        $result = '';
        $xval = $mk;
        $modulus = gmp_init('10000000000000000000000000000000000000000', 16);

        for ($i = 0; $i < 4; $i++) {
            $w0 = self::sha1_dss($xval);
            $xval = gmp_mod(gmp_add(gmp_add(gmp_init(bin2hex($xval), 16), gmp_init(bin2hex($w0), 16)), 1), $modulus);
            $xval = hex2bin(str_pad(gmp_strval($xval, 16), 40, '0', STR_PAD_LEFT));

            $w1 = self::sha1_dss($xval);
            $xval = gmp_mod(gmp_add(gmp_add(gmp_init(bin2hex($xval), 16), gmp_init(bin2hex($w1), 16)), 1), $modulus);
            $xval = hex2bin(str_pad(gmp_strval($xval, 16), 40, '0', STR_PAD_LEFT));

            $result .= $w0 . $w1;
        }

        return [
            'mk' => $mk,
            'encr' => substr($result, 0, 16),
            'aut' => substr($result, 16, 16),
            'msk' => substr($result, 32, 64),
            'emsk' => substr($result, 96, 64)
        ];
    }

    static private function sha1_dss($data)
    {
        if (strlen($data) != 20)
            throw new DataParseException("sha1-dss only support 20Bytes input");

        $h0 = 0x67452301;
        $h1 = 0xEFCDAB89;
        $h2 = 0x98BADCFE;
        $h3 = 0x10325476;
        $h4 = 0xC3D2E1F0;

        $f_rol = function ($n, $b) {
            return (($n << $b) | ($n >> (32 - $b))) & 0xffffffff;
        };

        $padding = str_repeat("\0", 44);
        $padded_data = $data . $padding;

        $thunks = str_split($padded_data, 64);
        foreach ($thunks as $thunk) {
            $w = array_merge(unpack('N16', $thunk), array_fill(0, 64, 0));
            for ($i = 16; $i < 80; $i++) {
                $w[$i] = $f_rol($w[$i - 3] ^ $w[$i - 8] ^ $w[$i - 14] ^ $w[$i - 16], 1);
            }

            $a = $h0;
            $b = $h1;
            $c = $h2;
            $d = $h3;
            $e = $h4;

            for ($i = 0; $i < 80; $i++) {
                if ($i < 20) {
                    $f = ($b & $c) | ((~$b) & $d);
                    $k = 0x5A827999;
                } elseif ($i < 40) {
                    $f = $b ^ $c ^ $d;
                    $k = 0x6ED9EBA1;
                } elseif ($i < 60) {
                    $f = ($b & $c) | ($b & $d) | ($c & $d);
                    $k = 0x8F1BBCDC;
                } else {
                    $f = $b ^ $c ^ $d;
                    $k = 0xCA62C1D6;
                }

                $temp = ($f_rol($a, 5) + $f + $e + $k + $w[$i]) & 0xffffffff;
                $e = $d;
                $d = $c;
                $c = $f_rol($b, 30);
                $b = $a;
                $a = $temp;
            }

            $h0 = ($h0 + $a) & 0xffffffff;
            $h1 = ($h1 + $b) & 0xffffffff;
            $h2 = ($h2 + $c) & 0xffffffff;
            $h3 = ($h3 + $d) & 0xffffffff;
            $h4 = ($h4 + $e) & 0xffffffff;
        }

        return pack('N', $h0) . pack('N', $h1) . pack('N', $h2) . pack('N', $h3) . pack('N', $h4);
    }

    static public function encrXFRMInfo($encr, $length, $key)
    {
        switch ($encr) {
            case self::ENCR_AES_CBC:
                switch ($length) {
                    case 128:
                    case 256:
                        return  "enc \"cbc(aes)\" \"0x" . bin2hex($key) . "\"";
                }
                break;
        }
        throw new DataParseException("ENCR not impl");
    }

    static public function encrNameWiresharkIKEInfo($encr, $length)
    {
        switch ($encr) {
            case self::ENCR_AES_CBC:
                switch ($length) {
                    case 256:
                        return "AES-CBC-256 [RFC3602]";
                    case 128:
                        return "AES-CBC-128 [RFC3602]";
                }
                break;
        }
        throw new DataParseException("ENCR not impl");
    }

    static public function encrNameWiresharkESPInfo($encr, $length)
    {
        switch ($encr) {
            case self::ENCR_AES_CBC:
                switch ($length) {
                    case 256:
                        return "AES-CBC-256 [RFC3602]";
                }
                break;
        }
        throw new DataParseException("ENCR not impl");
    }

    static public function encrGetOpenSSLNameInfo($encr, $length)
    {
        switch ($encr) {
            case self::ENCR_AES_CBC:
                switch ($length) {
                    case 128:
                        return "aes-128-cbc";
                    case 256:
                        return "aes-256-cbc";
                }
                break;
        }
        throw new DataParseException("ENCR not impl");
    }

    static public function encrIVLengthInfo($encr)
    {
        switch ($encr) {
            case self::ENCR_AES_CBC:
                return 16;
        }
        throw new DataParseException("ENCR not impl");
    }

    static public function encrKeyLengthInfo($encr_length)
    {
        return intval($encr_length / 8);
    }

    static public function integXFRMInfo($integ, $key)
    {
        switch ($integ) {
            case self::AUTH_HMAC_MD5_96:
                return "auth-trunc \"hmac(md5)\" \"0x" . bin2hex($key) . "\" 96";
            case self::AUTH_HMAC_SHA1_96:
                return "auth-trunc \"hmac(sha1)\" \"0x" . bin2hex($key) . "\" 96";
            case self::AUTH_HMAC_SHA2_256_128:
                return "auth-trunc \"hmac(sha256)\" \"0x" . bin2hex($key) . "\" 128";
        }
        throw new DataParseException("INTEG not impl");
    }

    static public function integNameWiresharkIKEInfo($integ)
    {
        switch ($integ) {
            case self::AUTH_HMAC_MD5_96:
                return "HMAC_MD5_96 [RFC2403]";
            case self::AUTH_HMAC_SHA1_96:
                return "HMAC_SHA1_96 [RFC2404]";
            case self::AUTH_HMAC_SHA2_256_128:
                return "HMAC_SHA2_256_128 [RFC4868]";
        }
        throw new DataParseException("INTEG not impl");
    }

    static public function integNameWiresharkESPInfo($integ)
    {
        switch ($integ) {
            case self::AUTH_HMAC_MD5_96:
                return "HMAC-MD5-96 [RFC2403]";
            case self::AUTH_HMAC_SHA1_96:
                return "HMAC-SHA-1-96 [RFC2404]";
            case self::AUTH_HMAC_SHA2_256_128:
                return "HMAC-SHA-256-128 [RFC4868]";
        }
        throw new DataParseException("INTEG not impl");
    }

    static public function integKeyLengthInfo($integ)
    {
        switch ($integ) {
            case self::AUTH_HMAC_MD5_96:
                return 16;
            case self::AUTH_HMAC_SHA1_96:
                return 20;
            case self::AUTH_HMAC_SHA2_256_128:
                return 32;
        }
        throw new DataParseException("INTEG not impl");
    }

    static public function integLengthInfo($integ)
    {
        switch ($integ) {
            case self::AUTH_HMAC_MD5_96:
                return 12;
            case self::AUTH_HMAC_SHA1_96:
                return 12;
            case self::AUTH_HMAC_SHA2_256_128:
                return 16;
        }
        throw new DataParseException("INTEG not impl");
    }

    static private function getMODP($length)
    {
        $length = intval($length);
        switch ($length) {
            case self::DH_GROUP_1024_MODP:
                return "FFFFFFFFFFFFFFFFC90FDAA22168C234C4C6628B80DC1CD129024E088A67CC74020BBEA63B139B22514A08798E3404DDEF9519B3CD3A431B302B0A6DF25F14374FE1356D6D51C245E485B576625E7EC6F44C42E9A637ED6B0BFF5CB6F406B7EDEE386BFB5A899FA5AE9F24117C4B1FE649286651ECE65381FFFFFFFFFFFFFFFF";
            case self::DH_GROUP_2048_MODP:
                return "ffffffffffffffffc90fdaa22168c234c4c6628b80dc1cd129024e088a67cc74020bbea63b139b22514a08798e3404ddef9519b3cd3a431b302b0a6df25f14374fe1356d6d51c245e485b576625e7ec6f44c42e9a637ed6b0bff5cb6f406b7edee386bfb5a899fa5ae9f24117c4b1fe649286651ece45b3dc2007cb8a163bf0598da48361c55d39a69163fa8fd24cf5f83655d23dca3ad961c62f356208552bb9ed529077096966d670c354e4abc9804f1746c08ca18217c32905e462e36ce3be39e772c180e86039b2783a2ec07a28fb5c55df06f4c52c9de2bcbf6955817183995497cea956ae515d2261898fa051015728e5a8aacaa68ffffffffffffffff";
        }
        throw new Exception("Invalid MODP length");
    }

    const ENCR_AES_CBC = 12;

    const PRF_HMAC_MD5 = 1;
    const PRF_HMAC_SHA1 = 2;
    const PRF_HMAC_SHA2_256 = 5;

    const AUTH_HMAC_MD5_96 = 1;
    const AUTH_HMAC_SHA1_96 = 2;
    const AUTH_HMAC_SHA2_256_128 = 12;

    const DH_GROUP_1024_MODP = 2;
    const DH_GROUP_2048_MODP = 14;
}
