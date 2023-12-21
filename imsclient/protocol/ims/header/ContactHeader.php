<?php

namespace imsclient\protocol\ims\header;

use imsclient\protocol\ims\uri\SIPUri;

class ContactHeader extends GenericHeader
{
    static protected $_name = "Contact";

    /** @var SIPUri */
    public $uri;
    public $imei;
    public $parameter = [];

    public function __construct(SIPUri $ineturi, $imei)
    {
        $this->uri = $ineturi;
        $this->imei = $imei;
    }

    public function addParameter($value)
    {
        $this->parameter[] = $value;
        return $this;
    }

    public function addInviteParameter()
    {
        $this->addParameter('+g.3gpp.icsi-ref="urn%3Aurn-7%3A3gpp-service.ims.icsi.mmtel"');
        $this->addParameter('+g.3gpp.mid-call');
        $this->addParameter('+g.3gpp.ps2cs-srvcc-orig-pre-alerting');
        $this->addParameter('+g.3gpp.srvcc-alerting');
        $this->addParameter("+sip.instance=\"<urn:gsma:imei:{$this->imei}>\"");
        return $this;
    }

    public function addSubscribeParameter()
    {
        $this->addParameter("+sip.instance=\"<urn:gsma:imei:{$this->imei}>\"");
        $this->addParameter('text');
        return $this;
    }

    public function addRegisterParameter()
    {
        $this->addParameter('+g.3gpp.icsi-ref="urn%3Aurn-7%3A3gpp-service.ims.icsi.mmtel"');
        $this->addParameter('+g.3gpp.mid-call');
        $this->addParameter('+g.3gpp.ps2cs-srvcc-orig-pre-alerting');
        $this->addParameter('+g.3gpp.smsip');
        $this->addParameter('+g.3gpp.srvcc-alerting');
        $this->addParameter("+sip.instance=\"<urn:gsma:imei:{$this->imei}>\"");
        $this->addParameter('text');
        return $this;
    }

    protected function generate()
    {
        $this->_value = "<{$this->uri}>";
        foreach ($this->parameter as $value) {
            $this->_value .= ";{$value}";
        }
    }
}
