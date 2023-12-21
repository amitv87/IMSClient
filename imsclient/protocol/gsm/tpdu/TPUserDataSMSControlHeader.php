<?php

namespace imsclient\protocol\gsm\tpdu;

use imsclient\datastruct\StringStream;

class TPUserDataSMSControlHeader extends TPUserDataGenericHeader
{
    static protected $iei = 0x00;
    static protected $length = 3;

    public $message_idenfier;
    public $message_parts;
    public $message_part_number;

    public function __construct($message_idenfier, $message_parts, $message_part_number)
    {
        $this->message_idenfier = $message_idenfier;
        $this->message_parts = $message_parts;
        $this->message_part_number = $message_part_number;
    }

    protected function generate(): string
    {
        $data = pack('C', $this->message_idenfier);
        $data .= pack('C', $this->message_parts);
        $data .= pack('C', $this->message_part_number);
        return $data;
    }

    protected function parse(StringStream $stream)
    {
        $this->message_idenfier = $stream->readU8();

        $this->message_parts = $stream->readU8();

        $this->message_part_number = $stream->readU8();
    }
}
