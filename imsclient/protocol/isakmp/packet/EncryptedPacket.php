<?php

namespace imsclient\protocol\isakmp\packet;

use imsclient\exception\DataParseException;
use imsclient\protocol\isakmp\payload\EncrypedAndAuthenticatedPayload;
use imsclient\protocol\isakmp\payload\GenericPayload;
use imsclient\protocol\isakmp\Transaction;
use imsclient\datastruct\StringStream;

class EncryptedPacket extends GenericPacket
{
    public function pack()
    {
        $crypto = $this->transaction->crypto;
        $payloads = $this->payload;

        $nextpayload = GenericPayload::prepareChain($payloads);
        $buffer = "";
        foreach ($payloads as $payload) {
            $buffer .= $payload->pack();
        }
        $bufferlength = strlen($buffer) + 1; // +1 for pad length byte
        $blocklength = $crypto->encrBlockLength();
        $paddedlength = ceil($bufferlength / $blocklength) * $blocklength;
        $padding = str_repeat("\x00", $paddedlength - $bufferlength);
        $buffer .= $padding . pack("C", strlen($padding));
        $iv = random_bytes($crypto->encrIVLength());
        $encrypted = $crypto->encrEncrypt($buffer, $crypto->sk_ei, $iv);
        $ic_dummy = str_repeat("\x00", $crypto->integLength());

        $container = new EncrypedAndAuthenticatedPayload();
        $container->nextpayload = $nextpayload;
        $container->raw = $iv . $encrypted . $ic_dummy;
        $this->payload = [$container];

        $buffer = parent::pack();
        $ic = $crypto->integHmac(substr($buffer, 0, strlen($buffer) - $crypto->integLength()), $crypto->sk_ai);
        $buffer = substr($buffer, 0, strlen($buffer) - $crypto->integLength()) . $ic;
        return $buffer;
    }

    protected function decrypt()
    {
        $crypto = $this->transaction->crypto;
        $payload = $this->payload[0];
        $iv = "";
        $ic = "";
        $encrypted = "";
        $stream = new StringStream($payload->getRawPayload());
        $iv = $stream->read($crypto->encrIVLength());
        $encrypted = $stream->read($stream->getLengthLeft() - $crypto->integLength());
        $ic = $stream->read($crypto->integLength());
        $verified_ike_packet = substr($this->_rawpacket, 0, strlen($this->_rawpacket) - $crypto->integLength());
        $ic_calc = $crypto->integHmac($verified_ike_packet, $crypto->sk_ar);
        if ($ic !== $ic_calc) {
            throw new DataParseException("Integrity check failed");
        }
        $decrypted = $crypto->encrDecrypt($encrypted, $crypto->sk_er, $iv);
        $stream = new StringStream($decrypted);
        $this->payload = GenericPayload::parseChain($payload->nextpayload, $stream);
    }

    static public function unpack(Transaction $t, string $data): static
    {
        $instance = parent::unpack($t, $data);
        if (count($instance->payload) != 1) {
            throw new DataParseException("EncryptedPacket must have exactly one payload");
        }
        if (!($instance->payload[0] instanceof EncrypedAndAuthenticatedPayload)) {
            throw new DataParseException("EncryptedPacket must have EncrypedAndAuthenticatedPayload");
        }
        $instance->decrypt();
        return $instance;
    }
}
