<?php

namespace imsclient\datastruct;

use imsclient\exception\DataParseException;

class StringStream
{
    private $stream;
    private $lengthleft;

    public function __construct($string)
    {
        $this->stream = fopen('php://memory', 'r+');
        fwrite($this->stream, $string);
        rewind($this->stream);
        $this->lengthleft = strlen($string);
    }

    public function hasData()
    {
        return boolval($this->lengthleft > 0);
    }

    public function read($length)
    {
        if ($length < 0)
            throw new DataParseException('Invalid length');
        if ($length == 0)
            return '';
        $data = fread($this->stream, $length);
        if ($data === false)
            throw new DataParseException('Failed to read from stream');

        $this->lengthleft -= $length;

        return $data;
    }

    public function readU8()
    {
        $data = $this->read(1);
        return unpack('C', $data)[1];
    }

    public function readU16BE()
    {
        $data = $this->read(2);
        return unpack('n', $data)[1];
    }

    public function readU32BE()
    {
        $data = $this->read(4);
        return unpack('N', $data)[1];
    }

    public function readU64BE()
    {
        $data = $this->read(8);
        return unpack('J', $data)[1];
    }

    public function readAll()
    {
        return $this->read($this->lengthleft);
    }

    public function getLengthLeft()
    {
        return $this->lengthleft;
    }

    public function __destruct()
    {
        if (is_resource($this->stream))
            fclose($this->stream);
    }
}
