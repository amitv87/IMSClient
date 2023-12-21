<?php

namespace imsclient\uiccprovider\pcsc\struct;

use imsclient\uiccprovider\pcsc\struct\T8L8V;
use Exception;
use imsclient\datastruct\StringStream;

class FCI
{
    public $file_struct; // u8
    public $file_type; // u8
    public $file_record_size; // u16be
    public $file_record_count; // u8
    public $file_size; // u16be

    static public function unpack($data): static
    {
        $instance = new static();
        $stream = new StringStream($data);
        $l1 = T8L8V::unpack($stream);
        if ($l1->tag != Tag::FCI) {
            throw new Exception("Unexpected tag");
        }

        $fd = null;
        $flength = null;
        $stream = new StringStream($l1->value);
        while ($stream->hasData()) {
            $tlv = T8L8V::unpack($stream);
            switch ($tlv->tag) {
                case Tag::FILE_DESCRIPTOR:
                    $fd = $tlv;
                    break;
                case Tag::FILE_LENGTH:
                    $flength = $tlv;
                    break;
            }
        }
        if ($fd === null) {
            throw new Exception("Missing file descriptor");
        }
        $stream = new StringStream($fd->value);
        switch (strlen($fd->value)) {
            case 2:
                $instance->file_struct = $stream->readU8();
                $instance->file_type = $stream->readU8();
                $instance->file_record_size = null;
                $instance->file_record_count = null;
                break;
            case 5:
                $instance->file_struct = $stream->readU8();
                $instance->file_type = $stream->readU8();
                $instance->file_record_size = $stream->readU16BE();
                $instance->file_record_count = $stream->readU8();
                break;
            default:
                throw new Exception("Unexpected file descriptor length");
        }

        if($flength != null)
        {
            $instance->file_size = unpack('n', $flength->value)[1];
        }

        return $instance;
    }

    const FILE_STRUCT_TRANSPARTENT = 0x41;
    const FILE_STRUCT_LINERFIXED = 0x42;
}
