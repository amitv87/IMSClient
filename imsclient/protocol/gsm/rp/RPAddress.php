<?php

namespace imsclient\protocol\gsm\rp;

use imsclient\exception\DataParseException;
use imsclient\datastruct\StringStream;
use ReflectionClass;

class RPAddress
{
    public $extension;
    public $number_type;
    public $numbering_plan;
    public $bcdnumber;

    public function __construct($bcdnumber)
    {
        $bcdnumber = ltrim($bcdnumber, '+');
        $this->bcdnumber = $bcdnumber;
        $this->extension = self::EXTENSION_NO;
        $this->number_type = self::NUMBER_TYPE_INTERNATIONAL;
        $this->numbering_plan = self::NUMBERING_PLAN_ISDN_TELEPHONY;
    }

    public function pack()
    {
        if ($this->bcdnumber == null) {
            return pack('C', 0);
        }
        $numberlen = round(strlen($this->bcdnumber) / 2);
        $number = str_pad($this->bcdnumber, $numberlen * 2, 'f', STR_PAD_RIGHT);
        $number = str_split($number, 2);
        array_walk($number, function (&$value) {
            $value = strrev($value);
        });
        $number = implode('', $number);
        $number = hex2bin($number);

        $length = $numberlen + 1;
        $infobyte = (($this->extension & 0x01) << 7 | ($this->number_type & 0x07) << 4 | ($this->numbering_plan & 0x0F)) & 0xFF;

        return pack('CC', $length, $infobyte) . $number;
    }

    static public function unpack(StringStream $stream): self
    {
        $reflection = new ReflectionClass(self::class);
        $instance = $reflection->newInstanceWithoutConstructor();

        $length = $stream->readU8();
        if ($length == 0) {
            return $instance;
        }
        $info = $stream->readU8();
        $instance->extension = ($info >> 7) & 0x01;
        $instance->number_type = ($info >> 4) & 0x07;
        $instance->numbering_plan = $info & 0x0F;

        $numberlen = $length - 1;
        $number = $stream->read($numberlen);
        if (strlen($number) !== $numberlen) {
            throw new DataParseException("RPAddress unpack failed: number length mismatch");
        }

        $number = strtolower(bin2hex($number));
        $number = str_split($number, 2);
        array_walk($number, function (&$value) {
            $value = strrev($value);
        });
        $number = implode('', $number);
        $number = rtrim($number, 'f');
        $instance->bcdnumber = $number;

        return $instance;
    }

    const EXTENSION_NO = 0x01;

    const NUMBER_TYPE_INTERNATIONAL = 0x01;
    const NUMBER_TYPE_NATIONAL = 0x02;

    const NUMBERING_PLAN_ISDN_TELEPHONY = 0x01;
}
