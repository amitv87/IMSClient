<?php

namespace imsclient\protocol\ims;

use imsclient\Identity;
use imsclient\IMSClient;

use imsclient\log\Logger;
use imsclient\exception\FatalException;

use imsclient\network\IMSSocketPool;
use imsclient\network\IPSecHelper;
use imsclient\protocol\ims\uri\SIPUri;
use imsclient\protocol\ims\header\ViaHeader;
use imsclient\protocol\ims\header\ContactHeader;
use imsclient\protocol\ims\header\CSeqHeader;
use imsclient\protocol\ims\header\StatusLine;
use imsclient\protocol\ims\header\WWWAuthenticateHeader;
use imsclient\protocol\ims\header\SecurityServerHeader;
use imsclient\protocol\ims\header\PAssociatedURIHeader;
use imsclient\protocol\ims\header\BasicSecurityHeaderParam;
use imsclient\protocol\ims\header\PAccessNetworkInfoHeader;
use imsclient\protocol\ims\packet\SubscribePacket;
use imsclient\protocol\ims\packet\RegisterPacket;
use imsclient\protocol\ims\packet\GenericPacket;
use imsclient\protocol\ims\Transaction;

class RegisterSequence
{
    /** @var IMSClient */
    private $main;

    /** @var Transaction */
    private $transaction;

    /** @var Identity */
    private $identity;

    /** @var IMSSocketPool */
    private $socket;

    public function __construct(IMSClient $main)
    {
        $this->main = $main;
        $this->identity = $main->getIdentity();
        $this->socket = $main->getSocketPool();
    }

    public function start()
    {
        $this->transaction = new Transaction();
        $pkt = new RegisterPacket($this->transaction, $this->main->getIdentity());
        $via = $pkt->getHeader(ViaHeader::getName());
        $via->port = $this->socket->initial()->tcp()->getSelfPort();
        $contact = $pkt->getHeader(ContactHeader::getName());
        $contact->uri->port = $this->socket->initial()->tcp()->getSelfPort();
        $buf = $pkt->__toString();
        Logger::info("Initial secure negotiation, username: {$this->identity->uri_client}");

        $this->main->waitStatusPacket($pkt->getHeader(CSeqHeader::getName()), [$this, 'state_REGISTER_INITIAL']);
        $this->socket->initial()->tcp()->send($buf);
    }

    public function state_REGISTER_INITIAL(GenericPacket $pkt)
    {
        if ($pkt->getHeader(StatusLine::getName())->code !== 401) {
            throw new FatalException("Expected IPSec and Digest challenge in 401 response");
        }
        Logger::info("Received server challenge & IPSec configration");
        $this->socket->closeInitial();

        $auth = $pkt->getHeader(WWWAuthenticateHeader::getName());
        if ($auth === null) {
            throw new FatalException("WWW-Authenticate header not found, no Digest challenge?");
        }
        $this->identity->authDigest($auth);

        $sserver = $pkt->getHeader(SecurityServerHeader::getName());
        if (!isset($sserver->params[0])) {
            throw new FatalException("SecurityServerHeader params[0] not found, no IPSec configration?");
        }
        foreach ($sserver->params as $param) {
            if ($param instanceof BasicSecurityHeaderParam) {
                if ($param->ealg === BasicSecurityHeaderParam::EALG_NULL)
                    $this->identity->security_verify = $param;
                break;
            }
        }
        if ($this->identity->security_verify == null) {
            throw new FatalException("Suitable SecurityServerHeaderParam not found");
        }

        $indicator = $this->identity->ip_addr_client;
        $responder = $this->identity->ip_addr_proxy;
        $encr = CryptoHelper::encrXFRMInfo($this->identity->security_verify->ealg);
        $auth = CryptoHelper::integXFRMInfo($this->identity->security_verify->alg, $this->identity->auth_ik);

        IPSecHelper::addTransportPair($this->identity->u32id, $this->identity->u32id_client_tx_pcscf_ports_mark, $indicator, $responder, $this->identity->security_client->spi_c, $this->identity->security_verify->spi_s, $encr, $encr, $auth, $auth);
        IPSecHelper::addTransportPair($this->identity->u32id, $this->identity->u32id_client_tx_pcscf_portc_mark, $indicator, $responder, $this->identity->security_client->spi_s, $this->identity->security_verify->spi_c, $encr, $encr, $auth, $auth);

        $this->socket->client()->setMark($this->identity->u32id_client_tx_pcscf_ports_mark);
        $this->socket->server()->setMark($this->identity->u32id_client_tx_pcscf_portc_mark);

        $this->socket->secure($this->identity->ip_addr_proxy, $this->identity->security_verify->port_s);

        $pkt = new RegisterPacket($this->transaction, $this->identity);
        $pkt->addHeader(new PAccessNetworkInfoHeader());
        $buf = $pkt->toString();

        Logger::info("Initial REGISTER...");
        $this->main->waitStatusPacket($pkt->getHeader(CSeqHeader::getName()), [$this, 'state_REGISTER_CONFIRM']);
        $this->socket->client()->tcp()->send($buf);
    }

    public function state_REGISTER_CONFIRM(GenericPacket $pkt)
    {
        if ($pkt->getHeader(StatusLine::getName())->code !== 200) {
            throw new FatalException("REGISTER failed, check credentials or try again later");
        }
        Logger::success("REGISTER OK!");
        $newuris = $pkt->getHeaders(PAssociatedURIHeader::getName());
        foreach ($newuris as $newuri) {
            if ($newuri->uri instanceof SIPUri) {
                $this->identity->uri_client = $newuri->uri;
                Logger::warning("Client URI changed to: [{$this->identity->uri_client}]");
                break;
            }
        }

        $this->subscribe();
    }

    public function subscribe()
    {
        $pkt = new SubscribePacket(new Transaction(), $this->identity);
        $buf = $pkt->__toString();

        Logger::info("Initial SUBSCRIBE...");
        $this->main->waitStatusPacket($pkt->getHeader(CSeqHeader::getName()), [$this, 'state_SUBSCRIBE_INITIAL']);
        $this->socket->client()->tcp()->send($buf);
    }

    public function state_SUBSCRIBE_INITIAL(GenericPacket $pkt)
    {
        if ($pkt->getHeader(StatusLine::getName())->code !== 200) {
            throw new FatalException("SUBSCRIBE failed, try again later");
        }
        Logger::success("SUBSCRIBE OK!");
        $this->main->onReady();
    }
}
