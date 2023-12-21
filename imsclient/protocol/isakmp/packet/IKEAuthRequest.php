<?php

namespace imsclient\protocol\isakmp\packet;

use imsclient\protocol\isakmp\payload\AuthenticationPayload;

class IKEAuthRequest extends EncryptedPacket
{
    protected static $_exchangetype = self::EXCHANGE_TYPE_IKE_AUTH;

    public function default()
    {
        $crypto = $this->transaction->crypto;
        $hash = $crypto->prfHmac($this->transaction->id_initiator->getRawPayload(), $this->transaction->crypto->sk_pi);
        $packet = $this->transaction->ike_sa_init_pkt_buffer . $this->transaction->nonce_responder . $hash;
        $tmpkey = $crypto->prfHmac("Key Pad for IKEv2", $crypto->eap_msk);
        $hash = $crypto->prfHmac($packet, $tmpkey);
        $this->payload[] = new AuthenticationPayload(AuthenticationPayload::METHOD_SHARED_KEY_MESSAGE_INTEGRITY_CODE, $hash);
    }
}
