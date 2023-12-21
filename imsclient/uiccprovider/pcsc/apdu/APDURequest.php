<?php

namespace imsclient\uiccprovider\pcsc\apdu;

use Exception;

abstract class APDURequest
{
    static protected $cla;
    static protected $ins;

    protected $p1;
    protected $p2;
    protected $l;
    protected $data;

    public function pack()
    {
        $this->generate();
        $this->_generate();
        $bytes = "";
        $bytes .= pack("C", static::$cla);
        $bytes .= pack("C", static::$ins);
        $bytes .= pack("C", $this->p1);
        $bytes .= pack("C", $this->p2);
        $bytes .= pack("C", $this->l);
        $bytes .= $this->data;
        return $bytes;
    }

    protected function generate()
    {
    }

    protected function _generate()
    {
        throw new Exception("Not implemented");
    }

    public function __toString()
    {
        return $this->pack();
    }
}
