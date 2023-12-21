<?php

namespace imsclient\protocol\ims\uri;

use imsclient\exception\DataParseException;

class CidUri extends GenericUri
{
    static protected $_proto = "cid";

    public $imsi;
    public $host;

    public function __construct($imsi, $host)
    {
        $this->imsi = $imsi;
        $this->host = $host;
    }

    public function generate()
    {
        $this->_value = $this->imsi . "@" . $this->host;
    }

    public function parse()
    {
        $explode = explode("@", $this->_value);
        if (count($explode) != 2) {
            throw new DataParseException("Invalid CidUri: |{$this->_value}|}");
        }

        $this->imsi = $explode[0];
        $this->host = $explode[1];
    }
}
