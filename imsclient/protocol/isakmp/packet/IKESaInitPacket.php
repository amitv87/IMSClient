<?php

namespace imsclient\protocol\isakmp\packet;

use imsclient\protocol\isakmp\CryptoHelper;
use imsclient\protocol\isakmp\payload\Attribute;
use imsclient\protocol\isakmp\payload\KeyExchangePayload;
use imsclient\protocol\isakmp\payload\NoncePayload;
use imsclient\protocol\isakmp\payload\NotifyPayload;
use imsclient\protocol\isakmp\payload\ProposalPayload;
use imsclient\protocol\isakmp\payload\SecurityAssociationPayload;
use imsclient\protocol\isakmp\payload\TransformPayload;

class IKESaInitPacket extends GenericPacket
{
    protected static $_exchangetype = self::EXCHANGE_TYPE_IKE_SA_INIT;

    protected function default()
    {
        // IKE SA Here
        $sa = new SecurityAssociationPayload([
            new ProposalPayload(ProposalPayload::PROTOCOL_ID_IKE, "", [
                new TransformPayload(TransformPayload::TYPE_ENCR, CryptoHelper::ENCR_AES_CBC, [
                    new Attribute(Attribute::FORMAT_TV, TransformPayload::ATTR_KEY_LENGTH, 128)
                ]),
                new TransformPayload(TransformPayload::TYPE_INTEG, CryptoHelper::AUTH_HMAC_MD5_96),
                new TransformPayload(TransformPayload::TYPE_PRF, CryptoHelper::PRF_HMAC_MD5),
                new TransformPayload(TransformPayload::TYPE_DH, $this->transaction->ike_dh)
            ]),
            new ProposalPayload(ProposalPayload::PROTOCOL_ID_IKE, "", [
                new TransformPayload(TransformPayload::TYPE_ENCR, CryptoHelper::ENCR_AES_CBC, [
                    new Attribute(Attribute::FORMAT_TV, TransformPayload::ATTR_KEY_LENGTH, 256)
                ]),
                new TransformPayload(TransformPayload::TYPE_INTEG, CryptoHelper::AUTH_HMAC_MD5_96),
                new TransformPayload(TransformPayload::TYPE_PRF, CryptoHelper::PRF_HMAC_MD5),
                new TransformPayload(TransformPayload::TYPE_DH, $this->transaction->ike_dh)
            ]),
            new ProposalPayload(ProposalPayload::PROTOCOL_ID_IKE, "", [
                new TransformPayload(TransformPayload::TYPE_ENCR, CryptoHelper::ENCR_AES_CBC, [
                    new Attribute(Attribute::FORMAT_TV, TransformPayload::ATTR_KEY_LENGTH, 128)
                ]),
                new TransformPayload(TransformPayload::TYPE_INTEG, CryptoHelper::AUTH_HMAC_SHA1_96),
                new TransformPayload(TransformPayload::TYPE_PRF, CryptoHelper::PRF_HMAC_SHA1),
                new TransformPayload(TransformPayload::TYPE_DH, $this->transaction->ike_dh)
            ]),
            new ProposalPayload(ProposalPayload::PROTOCOL_ID_IKE, "", [
                new TransformPayload(TransformPayload::TYPE_ENCR, CryptoHelper::ENCR_AES_CBC, [
                    new Attribute(Attribute::FORMAT_TV, TransformPayload::ATTR_KEY_LENGTH, 256)
                ]),
                new TransformPayload(TransformPayload::TYPE_INTEG, CryptoHelper::AUTH_HMAC_SHA1_96),
                new TransformPayload(TransformPayload::TYPE_PRF, CryptoHelper::PRF_HMAC_SHA1),
                new TransformPayload(TransformPayload::TYPE_DH, $this->transaction->ike_dh)
            ]),
            new ProposalPayload(ProposalPayload::PROTOCOL_ID_IKE, "", [
                new TransformPayload(TransformPayload::TYPE_ENCR, CryptoHelper::ENCR_AES_CBC, [
                    new Attribute(Attribute::FORMAT_TV, TransformPayload::ATTR_KEY_LENGTH, 128)
                ]),
                new TransformPayload(TransformPayload::TYPE_INTEG, CryptoHelper::AUTH_HMAC_SHA2_256_128),
                new TransformPayload(TransformPayload::TYPE_PRF, CryptoHelper::PRF_HMAC_SHA2_256),
                new TransformPayload(TransformPayload::TYPE_DH, $this->transaction->ike_dh)
            ]),
            new ProposalPayload(ProposalPayload::PROTOCOL_ID_IKE, "", [
                new TransformPayload(TransformPayload::TYPE_ENCR, CryptoHelper::ENCR_AES_CBC, [
                    new Attribute(Attribute::FORMAT_TV, TransformPayload::ATTR_KEY_LENGTH, 256)
                ]),
                new TransformPayload(TransformPayload::TYPE_INTEG, CryptoHelper::AUTH_HMAC_SHA2_256_128),
                new TransformPayload(TransformPayload::TYPE_PRF, CryptoHelper::PRF_HMAC_SHA2_256),
                new TransformPayload(TransformPayload::TYPE_DH, $this->transaction->ike_dh)
            ]),
        ]);

        $ke = new KeyExchangePayload($this->transaction->ike_dh, $this->transaction->crypto->getDHPublicKey());
        $nonce = new NoncePayload($this->transaction->nonce_initiator);

        $natdsrc = new NotifyPayload();
        $natdsrc->protocol_id = NotifyPayload::PROTOCOL_ID_NONE;
        $natdsrc->notify_type = NotifyPayload::TYPE_NAT_DETECTION_SOURCE_IP;
        $natdsrc->data = str_repeat("\x00", 20); // force NAT-T

        $natddst = new NotifyPayload();
        $natddst->protocol_id = NotifyPayload::PROTOCOL_ID_NONE;
        $natddst->notify_type = NotifyPayload::TYPE_NAT_DETECTION_DESTINATION_IP;
        $natddst->data = sha1($this->transaction->spi_initiator . $this->transaction->spi_responder .  pack('N', ip2long($this->transaction->addr_s)) . pack('n', $this->transaction->port_s), true);

        $this->payload[] = $sa;
        $this->payload[] = $ke;
        $this->payload[] = $nonce;
        $this->payload[] = $natdsrc;
        $this->payload[] = $natddst;
    }
}
