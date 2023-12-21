<?php

namespace imsclient\protocol\gsm\tpdu;

use imsclient\datastruct\gsmcharset\Converter;
use imsclient\datastruct\gsmcharset\Packer;
use imsclient\exception\DataParseException;
use imsclient\datastruct\StringStream;
use ReflectionClass;

class SMSDeliver
{
    /* Byte */
    public $rp;     //[7]
    public $udhi;   //[6]
    public $sri;    //[5]
    public $lp;     //[3]
    public $mms;    //[2]
    public $mti;    //[0,1]
    /* END Byte */

    /** @var TPAddress */
    public $originating;

    public $pid;

    /** @var TPDCS */
    public $dcs;

    /** @var TPTimestamp */
    public $timestamp;

    /** @var TPUserDataGenericHeader */
    public $userdata_header = [];

    public $userdata;

    public function getUserDataUTF8()
    {
        $sms_text = "";
        switch ($this->dcs->getEncoding()) {
            case TPDCS::GSM7BIT:
                $packer = new Packer();
                $sms_text = $packer->unpack($this->userdata);
                $converter = new Converter();
                $sms_text = $converter->convertGsmToUtf8($sms_text);
                break;
            case TPDCS::UCS2:
                $sms_text = $this->userdata;
                $sms_text = mb_convert_encoding($sms_text, 'UTF-8', 'UCS-2');
                break;
        }
        return $sms_text;
    }

    static public function unpack(string $data): self
    {
        $classname = self::class;
        $reflection = new ReflectionClass($classname);
        $instance = $reflection->newInstanceWithoutConstructor();

        $stream = new StringStream($data);

        $infobyte = $stream->readU8();

        $instance->mti = $infobyte & 0x03;
        $instance->mms = ($infobyte >> 2) & 0x01;
        $instance->lp = ($infobyte >> 3) & 0x01;
        $instance->sri = ($infobyte >> 5) & 0x01;
        $instance->udhi = ($infobyte >> 6) & 0x01;
        $instance->rp = ($infobyte >> 7) & 0x01;

        $instance->originating = TPAddress::unpack($stream);

        $instance->pid = $stream->readU8();

        $dcs = $stream->readU8();
        $instance->dcs = new TPDCS($dcs);

        $instance->timestamp = TPTimestamp::unpack($stream);

        $userdata_len = $stream->readU8();
        $userdata_header_len = 0;
        if ($instance->udhi) {
            $userdata_header_len = $stream->readU8();
        }
        $userdata_header_read_len = 0;
        while ($userdata_header_read_len < $userdata_header_len) {
            $userdata_header = TPUserDataGenericHeader::unpack($stream);
            $userdata_header_read_len += $userdata_header->getLength() + 2;
            $instance->userdata_header[] = $userdata_header;
        }
        if ($userdata_header_read_len != $userdata_header_len) {
            throw new DataParseException("SMSDeliver userdata_header_read_len != userdata_header_len");
        }
        $instance->userdata = $stream->read($userdata_len - $userdata_header_len);

        return $instance;
    }
}
