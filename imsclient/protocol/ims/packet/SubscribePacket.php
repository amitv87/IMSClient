<?php

namespace imsclient\protocol\ims\packet;

use imsclient\protocol\ims\header\AllowHeader;
use imsclient\protocol\ims\header\CallIDHeader;
use imsclient\protocol\ims\header\ContactHeader;
use imsclient\protocol\ims\header\CSeqHeader;
use imsclient\protocol\ims\header\EventHeader;
use imsclient\protocol\ims\header\ExpiresHeader;
use imsclient\protocol\ims\header\FromHeader;
use imsclient\protocol\ims\header\MaxForwardsHeader;
use imsclient\protocol\ims\header\PAccessNetworkInfoHeader;
use imsclient\protocol\ims\header\ProxyRequireHeader;
use imsclient\protocol\ims\header\RequestLine;
use imsclient\protocol\ims\header\RequireHeader;
use imsclient\protocol\ims\header\SecurityVerifyHeader;
use imsclient\protocol\ims\header\SessionIDHeader;
use imsclient\protocol\ims\header\SupportedHeader;
use imsclient\protocol\ims\header\ToHeader;
use imsclient\protocol\ims\header\UserAgentHeader;
use imsclient\protocol\ims\header\ViaHeader;
use imsclient\protocol\ims\uri\SIPUri;

class SubscribePacket extends RequestPacket
{
    protected function default()
    {
        $this->addHeader(new RequestLine("SUBSCRIBE", $this->identity->uri_client));
        $this->addHeader(new ExpiresHeader());
        $this->addHeader(new EventHeader("reg"));
        $this->addHeader(new FromHeader($this->identity->uri_client, $this->ts->tag));
        $this->addHeader(new ToHeader($this->identity->uri_client));
        $this->addHeader(new CallIDHeader($this->ts->callid));
        $this->addHeader(new SessionIDHeader($this->ts->sessionid));
        $this->addHeader((new ContactHeader(new SIPUri(null, $this->identity->ip_addr_client, $this->identity->security_client->port_s), $this->identity->imei))->addSubscribeParameter());
        $this->addHeader(new CSeqHeader("SUBSCRIBE"));
        $this->addHeader(new ViaHeader($this->identity->ip_proto_client, $this->identity->ip_addr_client, $this->identity->security_client->port_s, $this->ts->branch));
        $this->addHeader(new AllowHeader());
        $this->addHeader(new MaxForwardsHeader());
        $this->addHeader(new SupportedHeader());
        $this->addHeader(new UserAgentHeader());
        $this->addHeader(new PAccessNetworkInfoHeader());
        $this->addHeader((new SecurityVerifyHeader())->add($this->identity->security_verify));
        $this->addHeader(new RequireHeader());
        $this->addHeader(new ProxyRequireHeader());
    }
}
