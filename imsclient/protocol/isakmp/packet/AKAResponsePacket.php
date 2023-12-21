<?php

namespace imsclient\protocol\isakmp\packet;

use imsclient\protocol\isakmp\eap\EAPAKAAttribute;
use imsclient\protocol\isakmp\eap\EAPAKAResponse;
use imsclient\protocol\isakmp\payload\ExtensibleAuthenticationPayload;

class AKAResponsePacket extends EncryptedPacket
{
    protected static $_exchangetype = self::EXCHANGE_TYPE_IKE_AUTH;

    protected function default()
    {
        $attr_mac = new EAPAKAAttribute(EAPAKAAttribute::AT_MAC, "\x00\x00" . str_repeat("\x00", 16));

        $eap = new EAPAKAResponse($this->transaction->eap_id, EAPAKAResponse::SUBTYPE_CHALLENGE);
        $eap->attribute[] = new EAPAKAAttribute(EAPAKAAttribute::AT_RES, "\x00\x40{$this->transaction->crypto->eap_res}");
        $eap->attribute[] = $attr_mac;

        $buffer = $eap->pack();
        $attr_mac->value = "\x00\x00" . substr(hash_hmac('sha1', $buffer, $this->transaction->crypto->eap_aut, true), 0, 16);

        $ea = new ExtensibleAuthenticationPayload($eap);

        $this->payload[] = $ea;
    }
}
