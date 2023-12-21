<?php

namespace imsclient;

use imsclient\exception\DataParseException;
use imsclient\exception\FatalException;
use imsclient\log\Logger;
use imsclient\network\EventSocket;
use imsclient\network\IPSecHelper;
use imsclient\protocol\isakmp\CryptoHelper;
use imsclient\protocol\isakmp\eap\EAPAKAAttribute;
use imsclient\protocol\isakmp\eap\EAPAKARequest;
use imsclient\protocol\isakmp\eap\GenericEAP;
use imsclient\protocol\isakmp\packet\AKAResponsePacket;
use imsclient\protocol\isakmp\packet\GenericPacket;
use imsclient\protocol\isakmp\packet\IKEAuthInitiatorRequestPacket;
use imsclient\protocol\isakmp\packet\IKEAuthRequest;
use imsclient\protocol\isakmp\packet\IKESaInitPacket;
use imsclient\protocol\isakmp\payload\ConfigurationPayload;
use imsclient\protocol\isakmp\payload\ExtensibleAuthenticationPayload;
use imsclient\protocol\isakmp\payload\KeyExchangePayload;
use imsclient\protocol\isakmp\payload\NoncePayload;
use imsclient\protocol\isakmp\payload\SecurityAssociationPayload;
use imsclient\protocol\isakmp\payload\TransformPayload;
use imsclient\protocol\isakmp\Transaction;
use imsclient\uiccprovider\UICCProvider;

class SWuClient
{
    public readonly string $addr_client;
    public readonly string $addr_pcscf;

    private $mark;
    /** @var CardProvider */
    private $card;
    /** @var EventSocket */
    private $socket;
    /** @var EventSocket */
    private $socket_nat;
    /** @var Transaction */
    private $transaction;

    private $handover = false;

    private $responder_handler = [];

    public function __construct($imei, UICCProvider $card, $mark)
    {
        $this->mark = $mark;
        $this->card = $card;

        $imsi = $this->card->getIMSI();
        $mcc = $this->card->getMCC();
        $mnc = $this->card->getMNC();

        $this->transaction = new Transaction($imei, "0{$imsi}@nai.epc.mnc{$mnc}.mcc{$mcc}.3gppnetwork.org");
        $this->transaction->host_s = "epdg.epc.mnc{$mnc}.mcc{$mcc}.pub.3gppnetwork.org";
    }

    public function run()
    {
        Logger::info("SWu connection initializing...");
        $this->socket = new EventSocket(EventSocket::UDP, '0.0.0.0', 0);
        $this->socket_nat = new EventSocket(EventSocket::UDP, '0.0.0.0', 0);

        $host_addr = gethostbyname($this->transaction->host_s);
        $host_addr_mapped = IPSecHelper::addEndpointMap($this->mark, $host_addr);
        Logger::info("Generated ePDG Host: {$this->transaction->host_s}, Addr: {$host_addr}, Mapped to: {$host_addr_mapped}");
        $this->transaction->addr_s = $host_addr_mapped;
        $this->transaction->port_s = 500;

        $this->socket->onDataHandler = [$this, 'onData'];
        $this->socket_nat->onDataHandler = [$this, 'onDataNAT'];
        $this->socket->setMark($this->mark);
        $this->socket_nat->setMark($this->mark);
        $this->socket->connect($this->transaction->addr_s, $this->transaction->port_s);
        $this->socket_nat->connect($this->transaction->addr_s, 4500);

        $this->initial();

        while (!$this->handover) {
            EventSocket::select(1);
            $this->checkResponderPacket();
        }
    }

    private function initial()
    {
        Logger::info("Initial IKE MSG=00, username: {$this->transaction->id_initiator->data}");
        $pkt = new IKESaInitPacket($this->transaction);
        $buf = $pkt->pack();
        $this->waitResponder($pkt->messageid, [$this, 'handle_IKE_SA_INIT'], [[$this, 'send'], $buf]);
        $this->send($buf);
        $this->transaction->ike_sa_init_pkt_buffer = $buf;
        $this->transaction->port_s = 4500;
    }

    private function handle_IKE_SA_INIT(GenericPacket $pkt)
    {
        Logger::info("Receive IKE MSG=00 Reply");
        $this->socket->close();
        $this->socket = null;
        try {
            $nonce = NoncePayload::findOne($pkt->payload);
            $sa = SecurityAssociationPayload::findOne($pkt->payload);
            $pp = $sa->proposal[0] ?? null;
            $encr = null;
            $encr_length = null;
            $integ = null;
            $prf = null;
            foreach ($pp->transforms as $t) {
                switch ($t->type) {
                    case TransformPayload::TYPE_ENCR:
                        foreach ($t->attribute as $a) {
                            switch ($a->type) {
                                case TransformPayload::ATTR_KEY_LENGTH:
                                    $encr_length = $a->value;
                                    break;
                            }
                        }
                        $encr = $t->id;
                        break;
                    case TransformPayload::TYPE_INTEG:
                        $integ = $t->id;
                        break;
                    case TransformPayload::TYPE_PRF:
                        $prf = $t->id;
                        break;
                }
            }
            $ke = null;
            $kes = KeyExchangePayload::findAll($pkt->payload);
            foreach ($kes as $k) {
                if ($k->dh_group === $this->transaction->ike_dh) {
                    $ke = $k;
                    break;
                }
            }
        } catch (\Throwable $t) {
            throw new DataParseException($t->getMessage());
        }
        if ($ke === null || $encr === null || $encr_length === null || $integ === null || $prf === null) {
            throw new DataParseException("Critical info lost");
        }

        $this->transaction->setCryptoMode($encr, $encr_length, $integ, $prf);
        $this->transaction->spi_responder = $pkt->spi_responder;
        $this->transaction->setCryptoSPI();
        $this->transaction->nonce_responder = $nonce->data;
        $this->transaction->setCryptoNonce();
        $this->transaction->crypto->generateIKESecret($ke->data);
        $this->transaction->message_id++;

        $wireshark_decrypt = [bin2hex(pack('J', $this->transaction->crypto->spi_initiator)), bin2hex(pack('J', $this->transaction->crypto->spi_responder)), bin2hex($this->transaction->crypto->sk_ei), bin2hex($this->transaction->crypto->sk_er), '"' . $this->transaction->crypto->encrNameWiresharkIKE() . '"', bin2hex($this->transaction->crypto->sk_ai), bin2hex($this->transaction->crypto->sk_ar), '"' . $this->transaction->crypto->integNameWiresharkIKE() . '"'];
        $wireshark_decrypt = implode(",", $wireshark_decrypt);
        Logger::debug("Decrypt Table: {$wireshark_decrypt}");

        Logger::info("Initial secure IKE MID=01 EAP-AKA Request");
        $pkt = new IKEAuthInitiatorRequestPacket($this->transaction);
        $buf = $pkt->pack();
        $this->waitResponder($pkt->messageid, [$this, 'handle_IKE_AUTH_REQUEST'], [[$this, 'sendNAT'], $buf]);
        $this->sendNAT($buf);
    }

    private function handle_IKE_AUTH_REQUEST(GenericPacket $pkt)
    {
        Logger::info("Receive secure IKE MID=01");
        try {
            $ea = ExtensibleAuthenticationPayload::findOne($pkt->payload);
            $eap = $ea->eap;
            if (!($eap instanceof EAPAKARequest)) {
                throw new DataParseException("EAP Flow: Expect EAP-AKA Request");
            }
            $rand = null;
            $autn = null;
            $iv = null;
            $encr_data = null;
            foreach ($eap->attribute as $attr) {
                switch ($attr->type) {
                    case EAPAKAAttribute::AT_RAND:
                        $rand = substr($attr->value, 2);
                        break;
                    case EAPAKAAttribute::AT_AUTN:
                        $autn = substr($attr->value, 2);
                        break;
                    case EAPAKAAttribute::AT_IV:
                        $iv = substr($attr->value, 2);
                        break;
                    case EAPAKAAttribute::AT_ENCR_DATA:
                        $encr_data = substr($attr->value, 2);
                        break;
                }
            }
            if ($rand === null || $autn === null) {
                throw new DataParseException("Critical info lost");
            }
        } catch (\Throwable $t) {
            throw new DataParseException($t->getMessage());
        }
        $this->transaction->eap_id = $eap->id;
        $res = $this->card->auth($rand, $autn);
        if (!isset($res['res']) || !isset($res['ck']) || !isset($res['ik'])) {
            throw new DataParseException("SIM 3G Auth failed");
        }
        $this->transaction->crypto->eap($this->transaction->id_initiator->data, $res['res'], $res['ik'], $res['ck']);
        if ($iv !== null && $encr_data !== null) {
            $decrypted = $this->transaction->crypto->eap_decrypt($iv, $encr_data);
            $eap_decrypted = EAPAKAAttribute::unpack($decrypted);
            var_dump($eap_decrypted);
        }

        Logger::info("Initial secure IKE MID=02 EAP-AKA Response");
        $this->transaction->message_id++;
        $pkt = new AKAResponsePacket($this->transaction);
        $buf = $pkt->pack();
        $this->waitResponder($pkt->messageid, [$this, 'handle_EAP_AKA_RESULT'], [[$this, 'sendNAT'], $buf]);
        $this->sendNAT($buf);
    }

    private function handle_EAP_AKA_RESULT(GenericPacket $pkt)
    {
        Logger::info("Receive secure IKE MSG=02 EAP-AKA Result");
        try {
            $ea = ExtensibleAuthenticationPayload::findOne($pkt->payload);
            $eap = $ea->eap;
        } catch (\Throwable $t) {
            var_dump($pkt);
            throw new DataParseException($t->getMessage());
        }
        if ($eap->code !== GenericEAP::CODE_SUCCESS) {
            throw new FatalException("EAP Flow: EAP-AKA Failed");
        }
        Logger::success("EAP-AKA Success");
        Logger::info("Initial secure IKE MID=03 IKE_AUTH Request");
        $this->transaction->message_id++;
        $pkt = new IKEAuthRequest($this->transaction);
        $buf = $pkt->pack();
        $this->waitResponder($pkt->messageid, [$this, 'handle_IKE_AUTH_RESULT'], [[$this, 'sendNAT'], $buf]);
        $this->sendNAT($buf);
    }

    private function handle_IKE_AUTH_RESULT(GenericPacket $pkt)
    {
        Logger::info("Receive secure IKE MSG=03 IKE_AUTH Result");
        try {
            $conf = ConfigurationPayload::findOne($pkt->payload);
            $sa = SecurityAssociationPayload::findOne($pkt->payload);
            $pp = $sa->proposal[0] ?? null;
            $esp_responder_spi = unpack('N', $pp->spi)[1];
            $encr = null;
            $encr_length = null;
            $integ = null;
            foreach ($pp->transforms as $t) {
                switch ($t->type) {
                    case TransformPayload::TYPE_ENCR:
                        foreach ($t->attribute as $a) {
                            switch ($a->type) {
                                case TransformPayload::ATTR_KEY_LENGTH:
                                    $encr_length = $a->value;
                                    break;
                            }
                        }
                        $encr = $t->id;
                        break;
                    case TransformPayload::TYPE_INTEG:
                        $integ = $t->id;
                        break;
                }
            }
            $ipv4 = null;
            $pcscf4 = null;
            $ipv6 = null;
            $pcscf6 = null;
            foreach ($conf->attribute as $attr) {
                switch ($attr->type) {
                    case ConfigurationPayload::ATTR_INTERNAL_IP4_ADDRESS:
                        $addr = substr($attr->value, 0, 4);
                        $ipv4 = inet_ntop($addr) . "/32";
                        break;
                    case ConfigurationPayload::ATTR_INTERNAL_IP6_ADDRESS:
                        $addr = substr($attr->value, 0, 16);
                        $ipv6 = inet_ntop($addr) . "/128";
                        break;
                    case ConfigurationPayload::ATTR_P_CSCF_IP4_ADDRESS:
                        if ($pcscf4 === null) {
                            $pcscf4 = inet_ntop($attr->value);
                        }
                        break;
                    case ConfigurationPayload::ATTR_P_CSCF_IP6_ADDRESS:
                        if ($pcscf6 === null) {
                            $pcscf6 = inet_ntop($attr->value);
                        }
                        break;
                }
            }
        } catch (\Throwable $t) {
            throw new DataParseException($t->getMessage());
        }
        $this->transaction->esp_spi_responder = $esp_responder_spi;

        $pcscf = null;
        $xfrm_cidr = null;
        if ($ipv6 === null && $ipv4 === null) {
            throw new DataParseException("Critical info lost: INTERNAL_IP_ADDR");
        }
        if ($pcscf4 === null && $pcscf6 === null) {
            throw new DataParseException("Critical info lost: P_CSCF_IP_ADDR");
        }
        if ($pcscf6 !== null && $ipv6 !== null) {
            $pcscf = $pcscf6;
            $xfrm_cidr = $ipv6;
        } else if ($pcscf4 !== null && $ipv4 !== null) {
            $pcscf = $pcscf4;
            $xfrm_cidr = $ipv4;
        }
        if ($pcscf === null || $xfrm_cidr === null) {
            throw new DataParseException("P-CSCF INET4/6 mismatch");
        }
        $xfrm_ip = explode("/", $xfrm_cidr)[0];

        $keys = $this->transaction->crypto->generateESPSecret($encr_length, $integ);

        IPSecHelper::addTunnelInterface($this->mark, $this->socket_nat->getSelfAddr(), $this->socket_nat->getPeerAddr(), $this->transaction->esp_spi_initiator, $this->transaction->esp_spi_responder, CryptoHelper::encrXFRMInfo($encr, $encr_length, $keys['sk_ei']), CryptoHelper::encrXFRMInfo($encr, $encr_length, $keys['sk_er']), CryptoHelper::integXFRMInfo($integ, $keys['sk_ai']), CryptoHelper::integXFRMInfo($integ, $keys['sk_ar']), $this->socket_nat->getSelfPort(), $this->socket_nat->getPeerPort(), $xfrm_cidr, [$pcscf]);

        socket_set_option($this->socket_nat->getResource(), IPPROTO_UDP, UDP_ENCAP, UDP_ENCAP_ESPINUDP);

        $this->addr_client = $xfrm_ip;
        $this->addr_pcscf = $pcscf;

        Logger::success("SWu connection established");
        $this->handover = true;
    }

    public function send($data)
    {
        $this->socket->send($data);
    }

    public function sendNAT($data)
    {
        $this->socket_nat->send("\x00\x00\x00\x00{$data}");
    }

    public function onData(EventSocket $socket)
    {
        $data = $socket->read(1500);
        $pkt = GenericPacket::unpack($this->transaction, $data);
        $this->processResponderPacket($pkt);
    }

    public function onDataNAT(EventSocket $socket)
    {
        $data = $socket->read(1500);
        $marker = substr($data, 0, 4);
        $data = substr($data, 4); // Non-ESP Marker
        if ($marker === "\x00\x00\x00\x00") {
            $pkt = GenericPacket::unpack($this->transaction, $data);
            $this->processResponderPacket($pkt);
        } else {
            Logger::warning("Unhandled ESP Packet");
        }
    }

    public function waitResponder($messageid, callable $callback, $userdata = null, $timeout = 2)
    {
        $this->responder_handler[$messageid] = ['callback' => $callback, 'timestamp' => time(), 'timeout' => $timeout, "retry" => 0, "userdata" => $userdata];
    }

    private function processResponderPacket(GenericPacket $pkt)
    {
        if (isset($this->responder_handler[$pkt->messageid])) {
            $handler = $this->responder_handler[$pkt->messageid];
            unset($this->responder_handler[$pkt->messageid]);
            $handler['callback']($pkt, $handler['userdata']);
        } else {
            Logger::warning("Income Responder ISAKMP Packet Unhandled (MessageId: {$pkt->messageid})");
        }
    }

    private function checkResponderPacket()
    {
        foreach ($this->responder_handler as $messageid => &$reg) {
            if ($reg['timeout']) {
                $diff = time() - $reg['timestamp'];
                if ($diff > $reg['timeout']) {
                    if ($reg['retry']++ >= 5) {
                        throw new FatalException("Reply timeout, maybe network error");
                    }
                    Logger::warning("Retransmitting ISAKMP Packet (MessageId: {$messageid}, Retry: {$reg['retry']})");
                    $reg['timestamp'] = time();
                    $reg['userdata'][0]($reg['userdata'][1]);
                }
            }
        }
    }
}
