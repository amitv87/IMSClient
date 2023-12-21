<?php

namespace imsclient\protocol\isakmp\payload;

use imsclient\datastruct\StringStream;

class AuthenticationPayload extends GenericPayload
{
    static protected $tag = 39;
    public $method; // u8
    // Reversed u8[3]
    public $data;

    public function __construct($method, $data)
    {
        $this->method = $method;
        $this->data = $data;
    }

    protected function parse()
    {
        $stream = new StringStream($this->_payload);

        $this->method = $stream->readU8();
        $stream->readU8();
        $stream->readU8();
        $stream->readU8();
        $this->data = $stream->readALL();
    }

    protected function generate(): string
    {
        $bytes = "";
        $bytes .= pack('C', $this->method);
        $bytes .= pack('C', 0);
        $bytes .= pack('C', 0);
        $bytes .= pack('C', 0);
        $bytes .= $this->data;
        return $bytes;
    }

    const METHOD_SHARED_KEY_MESSAGE_INTEGRITY_CODE = 2;
}
