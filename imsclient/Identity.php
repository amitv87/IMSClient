<?php

namespace imsclient;

use imsclient\log\Logger;
use imsclient\protocol\ims\header\BasicSecurityHeaderParam;
use imsclient\protocol\ims\header\WWWAuthenticateHeader;
use imsclient\protocol\ims\uri\CidUri;
use imsclient\protocol\ims\uri\SIPUri;
use imsclient\uiccprovider\UICCProvider;
use imsclient\util\Utils;

class Identity
{
    public readonly string $cl_country;

    public readonly string $imei;
    public readonly string $imsi;
    public readonly string $realm;

    public readonly string $mcc;
    public readonly string $mnc;

    public $u32id;
    public readonly int $u32id_client_tx_pcscf_ports_mark;
    public readonly int $u32id_client_tx_pcscf_portc_mark;

    /** @var UICCProvider */
    private $card;

    /** @var string */
    public $ip_addr_client;
    /** @var string */
    public $ip_proto_client;
    /** @var string */
    public $ip_addr_proxy;
    /** @var string */
    public $ip_proto_proxy;

    /** @var SIPUri */
    public $uri_ims;
    /** @var SIPUri */
    public $uri_client;
    /** @var CidUri */
    public $uri_cid;

    /** @var BasicSecurityHeaderParam */
    public $security_client;
    /** @var BasicSecurityHeaderParam */
    public $security_verify;

    /** @var string */
    public $auth_nonce;
    /** @var string */
    public $auth_ck;
    /** @var string */
    public $auth_ik;
    /** @var string */
    public $auth_response;

    public function __construct(UICCProvider $card, $imei, $cl_country = 'IE')
    {
        $this->u32id = null;
        $this->cl_country = $cl_country;
        $this->card = $card;
        $this->imei = $imei;

        $this->imsi = $this->card->getIMSI();
        $this->mcc = $this->card->getMCC();
        $this->mnc = $this->card->getMNC();

        $this->u32id = crc32($this->imsi) & 0x0FFFFFFF | 0x80000000;
        $this->u32id_client_tx_pcscf_ports_mark = $this->u32id | 0x10000000;
        $this->u32id_client_tx_pcscf_portc_mark = $this->u32id | 0x20000000;

        $this->uri_cid = new CidUri($this->imsi, "ims.mnc{$this->mnc}.mcc{$this->mcc}.3gppnetwork.org");

        $ims = $this->card->getIMS();
        if ($ims == null)
            $this->uri_ims = new SIPUri(null, $this->uri_cid->host);
        else
            $this->uri_ims = new SIPUri(null, $ims);

        $this->uri_client = Utils::clone($this->uri_ims);
        $this->uri_client->username = $this->imsi;

        Logger::info("IMEI: {$this->imei}, IMSI: {$this->imsi}, MCC: {$this->mcc}, MNC: {$this->mnc}");
    }

    public function authDigest(WWWAuthenticateHeader $header)
    {
        $this->auth_nonce = $header->nonce;
        $auth_uri = $this->uri_ims;
        $auth_username = $this->uri_client->getValue();
        $auth_realm = $this->uri_client->host;

        $nonce = base64_decode($this->auth_nonce);
        $rand = substr($nonce, 0, 16);
        $autn = substr($nonce, 16, 16);
        $ret = $this->card->auth($rand, $autn);
        $res = $ret['res'];
        $this->auth_ck = $ret['ck'];
        $this->auth_ik = $ret['ik'];

        $A1 = "{$auth_username}:{$auth_realm}:{$res}";
        $A2 = "REGISTER:{$auth_uri}";
        $response = md5($A1) . ":{$this->auth_nonce}:" . md5($A2);
        $response = md5($response);
        Logger::debug("Digest Challenge H(Response): {$response}");

        $this->auth_response = $response;
        return $response;
    }

    public function setClientAddrInfo($ip_proto)
    {
        $this->ip_proto_client = $ip_proto;
    }

    public function getCardProvider()
    {
        return $this->card;
    }
}
