<?php

namespace imsclient\uiccprovider\pcsc\pcsclite;

use Exception;
use \FFI;

class Reader
{
    private PCSC $pcsc;
    private $name;

    public function __construct(PCSC $pcsc, $name)
    {
        $this->pcsc = $pcsc;
        $this->name = $name;
    }

    public function getCard(): ?Card
    {
        $ffi = $this->pcsc->getFFI();
        $hContext = $this->pcsc->getHContext();
        $hCard = $ffi->new("SCARDHANDLE");
        $dwActiveProtocol = $ffi->new("DWORD");
        $ret = $ffi->SCardConnect($hContext->cdata, $this->name, C::SCARD_SHARE_SHARED, C::SCARD_PROTOCOL_T0, FFI::addr($hCard), FFI::addr($dwActiveProtocol));
        if ($ret != 0) {
            $errstr = $ffi->pcsc_stringify_error($ret);
            for ($i = 0; $errstr[$i] != "\x00"; $i++) {
                echo $errstr[$i];
            }
            echo PHP_EOL;
            return null;
        }
        return new Card($this, $hCard);
    }

    public function getPCSC()
    {
        return $this->pcsc;
    }

    public function getName()
    {
        return $this->name;
    }
}
