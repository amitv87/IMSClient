<?php

namespace imsclient\protocol\ims\packet;

use imsclient\Identity;
use imsclient\protocol\ims\header\AcceptContactHeader;
use imsclient\protocol\ims\header\AllowHeader;
use imsclient\protocol\ims\header\CallIDHeader;
use imsclient\protocol\ims\header\ContentTypeHeader;
use imsclient\protocol\ims\header\CSeqHeader;
use imsclient\protocol\ims\header\FromHeader;
use imsclient\protocol\ims\header\MaxForwardsHeader;
use imsclient\protocol\ims\header\PAccessNetworkInfoHeader;
use imsclient\protocol\ims\header\PPreferredIdentityHeader;
use imsclient\protocol\ims\header\ProxyRequireHeader;
use imsclient\protocol\ims\header\RequestDispositionHeader;
use imsclient\protocol\ims\header\RequestLine;
use imsclient\protocol\ims\header\RequireHeader;
use imsclient\protocol\ims\header\SecurityVerifyHeader;
use imsclient\protocol\ims\header\SupportedHeader;
use imsclient\protocol\ims\header\ToHeader;
use imsclient\protocol\ims\header\UserAgentHeader;
use imsclient\protocol\ims\header\ViaHeader;
use imsclient\protocol\ims\Transaction;
use imsclient\protocol\ims\uri\GenericUri;

class MessagePacket extends RequestPacket
{
    /** @var GenericUri */
    protected $to;

    public function __construct(Transaction $ts, Identity $identity, GenericUri $to)
    {
        $this->to = $to;
        parent::__construct($ts, $identity);
    }

    protected function default()
    {
        $this->addHeader(new RequestLine("MESSAGE", $this->to));
        $this->addHeader(new CallIDHeader($this->ts->callid));
        $this->addHeader(new FromHeader($this->identity->uri_client, $this->ts->tag));
        $this->addHeader(new ToHeader($this->to));
        $this->addHeader(new RequestDispositionHeader());
        $this->addHeader(new AcceptContactHeader("*;+g.3gpp.smsip"));
        $this->addHeader(new CSeqHeader("MESSAGE"));
        $this->addHeader(new ViaHeader($this->identity->ip_proto_client, $this->identity->ip_addr_client, $this->identity->security_client->port_s, $this->ts->branch));
        $this->addHeader(new AllowHeader());
        $this->addHeader(new PPreferredIdentityHeader($this->identity->uri_client));
        $this->addHeader(new MaxForwardsHeader());
        $this->addHeader(new SupportedHeader());
        $this->addHeader(new UserAgentHeader());
        $this->addHeader(new PAccessNetworkInfoHeader());
        $this->addHeader((new SecurityVerifyHeader())->add($this->identity->security_verify));
        $this->addHeader(new RequireHeader());
        $this->addHeader(new ProxyRequireHeader());
        // Route
        $this->addHeader(new ContentTypeHeader('application/vnd.3gpp.sms'));
    }
}
