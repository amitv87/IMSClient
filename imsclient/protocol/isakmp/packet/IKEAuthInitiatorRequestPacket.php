<?php

namespace imsclient\protocol\isakmp\packet;

use imsclient\protocol\isakmp\CryptoHelper;
use imsclient\protocol\isakmp\payload\Attribute;
use imsclient\protocol\isakmp\payload\ConfigurationPayload;
use imsclient\protocol\isakmp\payload\DeviceIdentityNotifyPayload;
use imsclient\protocol\isakmp\payload\IdentificationPayload;
use imsclient\protocol\isakmp\payload\InitiatorTrafficSelectorPayload;
use imsclient\protocol\isakmp\payload\NotifyPayload;
use imsclient\protocol\isakmp\payload\ProposalPayload;
use imsclient\protocol\isakmp\payload\ResponderIdentificationPayload;
use imsclient\protocol\isakmp\payload\ResponderTrafficSelectorPayload;
use imsclient\protocol\isakmp\payload\SecurityAssociationPayload;
use imsclient\protocol\isakmp\payload\TrafficSelector;
use imsclient\protocol\isakmp\payload\TransformPayload;

class IKEAuthInitiatorRequestPacket extends EncryptedPacket
{
    protected static $_exchangetype = self::EXCHANGE_TYPE_IKE_AUTH;

    protected function default()
    {
        // ESP SA Here
        $sa = new SecurityAssociationPayload([
            new ProposalPayload(ProposalPayload::PROTOCOL_ID_ESP, pack('N', $this->transaction->esp_spi_initiator), [
                new TransformPayload(TransformPayload::TYPE_ENCR, CryptoHelper::ENCR_AES_CBC, [
                    new Attribute(Attribute::FORMAT_TV, TransformPayload::ATTR_KEY_LENGTH, 128)
                ]),
                new TransformPayload(TransformPayload::TYPE_INTEG, CryptoHelper::AUTH_HMAC_MD5_96),
                new TransformPayload(TransformPayload::TYPE_ESN, 0)
            ]),
            new ProposalPayload(ProposalPayload::PROTOCOL_ID_ESP, pack('N', $this->transaction->esp_spi_initiator), [
                new TransformPayload(TransformPayload::TYPE_ENCR, CryptoHelper::ENCR_AES_CBC, [
                    new Attribute(Attribute::FORMAT_TV, TransformPayload::ATTR_KEY_LENGTH, 256)
                ]),
                new TransformPayload(TransformPayload::TYPE_INTEG, CryptoHelper::AUTH_HMAC_MD5_96),
                new TransformPayload(TransformPayload::TYPE_ESN, 0)
            ]),
            new ProposalPayload(ProposalPayload::PROTOCOL_ID_ESP, pack('N', $this->transaction->esp_spi_initiator), [
                new TransformPayload(TransformPayload::TYPE_ENCR, CryptoHelper::ENCR_AES_CBC, [
                    new Attribute(Attribute::FORMAT_TV, TransformPayload::ATTR_KEY_LENGTH, 128)
                ]),
                new TransformPayload(TransformPayload::TYPE_INTEG, CryptoHelper::AUTH_HMAC_SHA1_96),
                new TransformPayload(TransformPayload::TYPE_ESN, 0)
            ]),
            new ProposalPayload(ProposalPayload::PROTOCOL_ID_ESP, pack('N', $this->transaction->esp_spi_initiator), [
                new TransformPayload(TransformPayload::TYPE_ENCR, CryptoHelper::ENCR_AES_CBC, [
                    new Attribute(Attribute::FORMAT_TV, TransformPayload::ATTR_KEY_LENGTH, 256)
                ]),
                new TransformPayload(TransformPayload::TYPE_INTEG, CryptoHelper::AUTH_HMAC_SHA1_96),
                new TransformPayload(TransformPayload::TYPE_ESN, 0)
            ]),
            new ProposalPayload(ProposalPayload::PROTOCOL_ID_ESP, pack('N', $this->transaction->esp_spi_initiator), [
                new TransformPayload(TransformPayload::TYPE_ENCR, CryptoHelper::ENCR_AES_CBC, [
                    new Attribute(Attribute::FORMAT_TV, TransformPayload::ATTR_KEY_LENGTH, 128)
                ]),
                new TransformPayload(TransformPayload::TYPE_INTEG, CryptoHelper::AUTH_HMAC_SHA2_256_128),
                new TransformPayload(TransformPayload::TYPE_ESN, 0)
            ]),
            new ProposalPayload(ProposalPayload::PROTOCOL_ID_ESP, pack('N', $this->transaction->esp_spi_initiator), [
                new TransformPayload(TransformPayload::TYPE_ENCR, CryptoHelper::ENCR_AES_CBC, [
                    new Attribute(Attribute::FORMAT_TV, TransformPayload::ATTR_KEY_LENGTH, 256)
                ]),
                new TransformPayload(TransformPayload::TYPE_INTEG, CryptoHelper::AUTH_HMAC_SHA2_256_128),
                new TransformPayload(TransformPayload::TYPE_ESN, 0)
            ]),
        ]);

        $iid = $this->transaction->id_initiator;
        $rid = new ResponderIdentificationPayload(IdentificationPayload::TYPE_FQDN, "ims");

        $conf = new ConfigurationPayload(ConfigurationPayload::TYPE_CFG_REQUEST);
        $conf->attribute[] = new Attribute(Attribute::FORMAT_TLV, ConfigurationPayload::ATTR_INTERNAL_IP4_ADDRESS);
        $conf->attribute[] = new Attribute(Attribute::FORMAT_TLV, ConfigurationPayload::ATTR_INTERNAL_IP4_DNS);
        $conf->attribute[] = new Attribute(Attribute::FORMAT_TLV, ConfigurationPayload::ATTR_INTERNAL_IP6_ADDRESS);
        $conf->attribute[] = new Attribute(Attribute::FORMAT_TLV, ConfigurationPayload::ATTR_INTERNAL_IP6_DNS);
        $conf->attribute[] = new Attribute(Attribute::FORMAT_TLV, ConfigurationPayload::ATTR_P_CSCF_IP4_ADDRESS);
        $conf->attribute[] = new Attribute(Attribute::FORMAT_TLV, ConfigurationPayload::ATTR_P_CSCF_IP6_ADDRESS);

        $its = new InitiatorTrafficSelectorPayload();
        $its->selector[] = new TrafficSelector(TrafficSelector::TS_IPV4_ADDR_RANGE, 0, "\x00\x00\xff\xff" . str_repeat("\x00", 4) . str_repeat("\xff", 4));
        $its->selector[] = new TrafficSelector(TrafficSelector::TS_IPV6_ADDR_RANGE, 0, "\x00\x00\xff\xff" . str_repeat("\x00", 16) . str_repeat("\xff", 16));

        $rts = new ResponderTrafficSelectorPayload();
        $rts->selector[] = new TrafficSelector(TrafficSelector::TS_IPV4_ADDR_RANGE, 0, "\x00\x00\xff\xff" . str_repeat("\x00", 4) . str_repeat("\xff", 4));
        $rts->selector[] = new TrafficSelector(TrafficSelector::TS_IPV6_ADDR_RANGE, 0, "\x00\x00\xff\xff" . str_repeat("\x00", 16) . str_repeat("\xff", 16));

        $eaponlynotify = new NotifyPayload();
        $eaponlynotify->protocol_id = NotifyPayload::PROTOCOL_ID_NONE;
        $eaponlynotify->notify_type = NotifyPayload::TYPE_EAP_ONLY_AUTHENTICATION;

        $imeisvnotify = new DeviceIdentityNotifyPayload();
        $imeisvnotify->imei = $this->transaction->imei;

        $this->payload[] = $iid;
        $this->payload[] = $rid;
        $this->payload[] = $conf;
        $this->payload[] = $sa;
        $this->payload[] = $its;
        $this->payload[] = $rts;
        $this->payload[] = $eaponlynotify;
        $this->payload[] = $imeisvnotify;
    }
}
