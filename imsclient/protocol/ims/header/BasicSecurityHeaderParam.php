<?php

namespace imsclient\protocol\ims\header;

class BasicSecurityHeaderParam
{
    public $mechanism;
    public $alg;
    public $ealg;
    public $mod;
    public $port_c;
    public $port_s;
    public $prot;
    public $spi_c;
    public $spi_s;
    public $q;

    public function __construct($alg, $ealg, $port_c, $port_s, $spi_c, $spi_s, $prot = self::PROT_ESP, $mod = self::MOD_TRANS, $mechanism = self::MECHANISM_IPSEC_3GPP, $q = null)
    {
        $this->mechanism = $mechanism;
        $this->alg = $alg;
        $this->ealg = $ealg;
        $this->mod = $mod;
        $this->port_c = $port_c;
        $this->port_s = $port_s;
        $this->prot = $prot;
        $this->spi_c = $spi_c;
        $this->spi_s = $spi_s;
        $this->q = $q;
    }

    static public function fromString($str)
    {
        $params = explode(';', $str);
        $mechanism = array_shift($params);
        $params = array_map(function ($param) {
            return explode('=', trim($param));
        }, $params);
        $params = array_combine(array_column($params, 0), array_column($params, 1));
        $alg = $params['alg'];
        $ealg = $params['ealg'];
        $mod = $params['mod'] ?? null;
        $port_c = $params['port-c'];
        $port_s = $params['port-s'];
        $prot = $params['prot'] ?? null;
        $spi_c = $params['spi-c'];
        $spi_s = $params['spi-s'];
        $q = $params['q'] ?? null;

        return new self($alg, $ealg, $port_c, $port_s, $spi_c, $spi_s, $prot, $mod, $mechanism, $q);
    }

    public function toString()
    {
        $ret = "$this->mechanism;alg=$this->alg;ealg=$this->ealg;mod=$this->mod;port-c=$this->port_c;port-s=$this->port_s;prot=$this->prot;spi-c=$this->spi_c;spi-s=$this->spi_s";
        if ($this->q)
            $ret .= ";q=$this->q";
        return $ret;
    }

    public function __toString()
    {
        return $this->toString();
    }

    const ALG_HMAC_MD5_96 = 'hmac-md5-96';
    const ALG_HMAC_SHA1_96 = 'hmac-sha-1-96';

    const EALG_NULL = 'null';
    const EALG_AES_CBC = 'aes-cbc';

    const PROT_ESP = 'esp';

    const MOD_TRANS = 'trans';

    const MECHANISM_IPSEC_3GPP = 'ipsec-3gpp';
}
