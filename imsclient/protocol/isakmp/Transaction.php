<?php

namespace imsclient\protocol\isakmp;

use imsclient\protocol\isakmp\payload\InitiatorIdentificationPayload;

class Transaction
{
    public $imei;

    /** @var InitiatorIdentificationPayload */
    public $id_initiator;

    public $spi_initiator;
    public $spi_responder;

    public $message_id;

    public $host_s;
    public $addr_s;
    public $port_s;
    public $addr_c;
    public $port_c;

    public $ike_encr;
    public $ike_encr_keylen;
    public $ike_integ;
    public $ike_prf;
    public $ike_dh;

    public $esp_spi_initiator;
    public $esp_spi_responder;

    public $eap_id;

    /** @var CryptoHelper */
    public $crypto;
    public $nonce_initiator;
    public $nonce_responder;

    public $ike_sa_init_pkt_buffer;

    public function __construct($imei, $nai)
    {
        $this->imei = $imei;
        $this->id_initiator = new InitiatorIdentificationPayload(InitiatorIdentificationPayload::TYPE_ID_RFC822_ADDR, $nai);
        $this->id_initiator->pack();
        $this->nonce_initiator = random_bytes(16);
        $this->spi_initiator = mt_rand(1, 0xffffffff);
        $this->esp_spi_initiator = mt_rand(1, 0xffffffff);
        $this->spi_responder = 0;
        $this->message_id = 0;
        $this->ike_dh = CryptoHelper::DH_GROUP_1024_MODP;
        $this->crypto = new CryptoHelper();
        $this->crypto->setCryptoDH($this->ike_dh);
    }

    public function setCryptoMode($encr, $encr_keylen, $integ, $prf)
    {
        $this->ike_encr = $encr;
        $this->ike_encr_keylen = $encr_keylen;
        $this->ike_integ = $integ;
        $this->ike_prf = $prf;

        $this->crypto->setCryptoMode($this->ike_prf, $this->ike_integ, $this->ike_encr, $this->ike_encr_keylen);
    }

    public function setCryptoSPI()
    {
        $this->crypto->setSPI($this->spi_initiator, $this->spi_responder);
    }

    public function setCryptoNonce()
    {
        $this->crypto->setNonce($this->nonce_initiator, $this->nonce_responder);
    }
}
