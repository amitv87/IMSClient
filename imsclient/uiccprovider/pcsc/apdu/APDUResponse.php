<?php

namespace imsclient\uiccprovider\pcsc\apdu;

class APDUResponse
{
    public $data;
    public $sw1;
    public $sw2;

    static public function unpack(string $data)
    {
        $instance = new static();
        $instance->data = substr($data, 0, -2);
        $instance->sw1 = unpack('C', substr($data, -2, 1))[1];
        $instance->sw2 = unpack('C', substr($data, -1, 1))[1];
        return $instance;
    }

    public function __debugInfo()
    {
        return [
            'data' => bin2hex($this->data),
            'sw1' => dechex($this->sw1),
            'sw2' => dechex($this->sw2),
        ];
    }
}
