<?php

namespace imsclient\uiccprovider\pcsc\struct;

use imsclient\uiccprovider\pcsc\struct\T8L8V;
use Exception;
use imsclient\datastruct\StringStream;

class EFDIRRecord
{
    public $aid;
    public $label;

    static public function unpack($data)
    {
        $instance = new static();
        $stream = new StringStream($data);

        $l1 = T8L8V::unpack($stream);
        if ($l1->tag != Tag::RECORD) {
            if ($l1->tag == 0xFF) {
                $instance->aid = null;
                $instance->label = null;
                return $instance;
            }
            throw new Exception("Unexpected tag");
        }

        $stream = new StringStream($l1->value);
        $aid = null;
        $label = null;
        while ($stream->hasData()) {
            $tlv = T8L8V::unpack($stream);
            switch ($tlv->tag) {
                case Tag::RECORD_AID:
                    $aid = $tlv;
                    break;
                case Tag::RECORD_LABEL:
                    $label = $tlv;
                    break;
            }
        }
        if ($aid === null || $label === null) {
            throw new Exception("Missing record AID or label");
        }
        $instance->aid = $aid->value;
        $instance->label = $label->value;
        return $instance;
    }
}
