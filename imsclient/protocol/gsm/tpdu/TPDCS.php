<?php

namespace imsclient\protocol\gsm\tpdu;

use imsclient\exception\DataParseException;

class TPDCS
{
    public $coding_group;   //[7,8]
    public $text_compress;  //[5]
    public $character_set;  //[2,3]
    public $message_class;  //[0,1]

    public function __construct($dcs)
    {
        $this->message_class = $dcs & 0x03;
        $this->character_set = ($dcs >> 2) & 0x03;
        $this->text_compress = ($dcs >> 5) & 0x01;
        $this->coding_group = ($dcs >> 7) & 0x03;
    }

    public function pack()
    {
        $dcs = 0x00;
        $dcs |= $this->message_class & 0x03;
        $dcs |= ($this->character_set & 0x03) << 2;
        $dcs |= ($this->text_compress & 0x01) << 5;
        $dcs |= ($this->coding_group & 0x03) << 7;
        return pack('C', $dcs);
    }

    public function getEncoding()
    {
        if ($this->coding_group === 0x00  && $this->character_set === 0x00) {
            return self::GSM7BIT;
        }

        if ($this->coding_group === 0x00  && $this->character_set === 0x02) {
            return self::UCS2;
        }

        throw new DataParseException("Unknown DCS");
    }

    const GSM7BIT = 0;
    const UCS2 = 8;
}
