<?php

namespace imsclient\protocol\ims\packet;

use imsclient\protocol\ims\header\AllowHeader;
use imsclient\protocol\ims\header\AuthorizationHeader;
use imsclient\protocol\ims\header\CallIDHeader;
use imsclient\protocol\ims\header\ContactHeader;
use imsclient\protocol\ims\header\ContentTypeHeader;
use imsclient\protocol\ims\header\CSeqHeader;
use imsclient\protocol\ims\header\ExpiresHeader;
use imsclient\protocol\ims\header\FromHeader;
use imsclient\protocol\ims\header\GeolocationHeader;
use imsclient\protocol\ims\header\GeolocationRoutingHeader;
use imsclient\protocol\ims\header\MaxForwardsHeader;
use imsclient\protocol\ims\header\ProxyRequireHeader;
use imsclient\protocol\ims\header\RequestLine;
use imsclient\protocol\ims\header\RequireHeader;
use imsclient\protocol\ims\header\SecurityClientHeader;
use imsclient\protocol\ims\header\SecurityVerifyHeader;
use imsclient\protocol\ims\header\SessionIDHeader;
use imsclient\protocol\ims\header\SupportedHeader;
use imsclient\protocol\ims\header\ToHeader;
use imsclient\protocol\ims\header\UserAgentHeader;
use imsclient\protocol\ims\header\ViaHeader;
use imsclient\protocol\ims\uri\SIPUri;

class RegisterPacket extends RequestPacket
{
    protected function default()
    {
        $this->addHeader(new RequestLine("REGISTER", $this->identity->uri_ims));
        $this->addHeader(new ToHeader($this->identity->uri_client));
        $this->addHeader(new FromHeader($this->identity->uri_client, $this->ts->tag));
        $this->addHeader(new ExpiresHeader());
        $this->addHeader(new RequireHeader());
        $this->addHeader(new ProxyRequireHeader());
        $this->addHeader((new SecurityClientHeader())->add($this->identity->security_client));
        $this->addHeader(new CallIDHeader($this->ts->callid));
        $this->addHeader(new SessionIDHeader($this->ts->sessionid));
        $this->addHeader(new GeolocationHeader($this->identity->uri_cid));
        $this->addHeader(new GeolocationRoutingHeader());
        $this->addHeader((new ContactHeader(new SIPUri(null, $this->identity->ip_addr_client, $this->identity->security_client->port_s), $this->identity->imei))->addRegisterParameter());
        $this->addHeader(new AuthorizationHeader($this->identity->uri_ims, $this->identity->uri_client, $this->identity->auth_nonce, $this->identity->auth_response));
        $this->addHeader(new CSeqHeader("REGISTER"));
        $this->addHeader(new ViaHeader($this->identity->ip_proto_client, $this->identity->ip_addr_client, $this->identity->security_client->port_s, $this->ts->branch));
        $this->addHeader(new AllowHeader());
        $this->addHeader(new MaxForwardsHeader());
        $this->addHeader(new SupportedHeader());
        $this->addHeader(new UserAgentHeader());
        if ($this->identity->security_verify != null) {
            $this->addHeader((new SecurityVerifyHeader())->add($this->identity->security_verify));
        }
        $this->addHeader(new ContentTypeHeader('application/pidf+xml'));
        $this->appendBody("<?xml version=\"1.0\"?>\n<presence xmlns=\"urn:ietf:params:xml:ns:pidf\" xmlns:dm=\"urn:ietf:params:xml:ns:pidf:data-model\" xmlns:gp=\"urn:ietf:params:xml:ns:pidf:geopriv10\" xmlns:gml=\"http://www.opengis.net/gml\" xmlns:gs=\"http://www.opengis.net/pidflo/1.0\" xmlns:cl=\"urn:ietf:params:xml:ns:pidf:geopriv10:civicAddr\" entity=\"{$this->identity->uri_client}\">\n<dm:device id=\"Wifi\">\n<gp:geopriv>\n<gp:location-info>\n<cl:civicAddress>\n<cl:country>{$this->identity->cl_country}</cl:country>\n</cl:civicAddress>\n</gp:location-info>\n<gp:usage-rules/>\n</gp:geopriv>\n<dm:timestamp>" . date("Y-m-d\TH:i:s\Z") . "</dm:timestamp>\n</dm:device>\n</presence>");
    }
}
