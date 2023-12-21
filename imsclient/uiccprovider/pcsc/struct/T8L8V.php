<?php

namespace imsclient\uiccprovider\pcsc\struct;

class T8L8V extends TLV
{
    static protected $tag_reader = 'readU8';
    static protected $tag_packer = 'C';
    static protected $length_reader = 'readU8';
    static protected $length_packer = 'C';
}
