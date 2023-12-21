<?php

namespace imsclient\protocol\ims\header;

use imsclient\exception\DataParseException;

class CSeqHeader extends GenericHeader
{
    static protected $_name = "CSeq";

    static protected $_counter = 1;

    public $seq;
    public $method;

    public function __construct($method, $seq = null)
    {
        if ($seq === null) {
            $seq = self::$_counter++;
        }
        $this->seq = $seq;
        $this->method = $method;
    }

    protected function generate()
    {
        $this->_value = $this->seq . " " . $this->method;
    }

    protected function parse()
    {
        $explode = explode(" ", $this->_value);
        if (count($explode) != 2) {
            throw new DataParseException("Invalid CSeq header: |{$this->_value}|}");
        }

        $this->seq = $explode[0];
        $this->method = $explode[1];
    }
}
