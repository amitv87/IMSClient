<?php

namespace imsclient\protocol\isakmp\payload;

use imsclient\datastruct\StringStream;

class DeviceIdentityNotifyPayload extends NotifyPayload
{
    public $imei;

    public function __construct()
    {
    }

    protected function parse()
    {
        return parent::parse();
    }

    protected function generate(): string
    {
        $this->protocol_id = NotifyPayload::PROTOCOL_ID_NONE;
        $this->notify_type = NotifyPayload::TYPE_DEVICE_IDENTITY;

        $numberbyteslen = round(strlen($this->imei) / 2);
        $number = str_pad($this->imei, $numberbyteslen * 2, 'f', STR_PAD_RIGHT);
        $number = str_split($number, 2);
        array_walk($number, function (&$value) {
            $value = strrev($value);
        });
        $number = implode('', $number);
        $number = hex2bin($number);

        $this->data = pack('n', strlen($number) + 1) . "\x02" . $number;

        return parent::generate();
    }
}
