<?php

namespace imsclient\uiccprovider\pcsc\pcsclite;

use Exception;
use \FFI;
use \FFI\CData;

class PCSC
{
    private FFI $ffi;
    private CData $hContext;

    public function __construct($pathToSoFile = '/usr/lib/x86_64-linux-gnu/libpcsclite.so')
    {
        $this->ffi = FFI::cdef(C::HEADER_DEF, $pathToSoFile);
        $ffi = $this->ffi;

        $this->hContext = $ffi->new("SCARDCONTEXT");
        $ret = $ffi->SCardEstablishContext(C::SCARD_SCOPE_SYSTEM, null, null, FFI::addr($this->hContext));
        if ($ret != 0) {
            $errstr = $ffi->pcsc_stringify_error($ret);
            for ($i = 0; $errstr[$i] != "\x00"; $i++) {
                echo $errstr[$i];
            }
            echo PHP_EOL;
            throw new Exception("SCardEstablishContext failed");
        }
    }

    public function getReaders()
    {
        $ffi = $this->ffi;
        $return = [];
        $dwReaders = $ffi->new("DWORD");
        $dwReaders->cdata = C::SCARD_AUTOALLOCATE;
        $mszReaders = $ffi->new("LPSTR");
        $mszReadersLP = $ffi->cast('LPSTR', FFI::addr($mszReaders));
        $ret = $ffi->SCardListReaders($this->hContext->cdata, null, $mszReadersLP, FFI::addr($dwReaders));
        if ($ret != 0) {
            $errstr = $ffi->pcsc_stringify_error($ret);
            for ($i = 0; $errstr[$i] != "\x00"; $i++) {
                echo $errstr[$i];
            }
            echo PHP_EOL;
            throw new Exception("SCardListReaders failed");
        }
        $buf = "";
        for ($i = 0;; $i++) {
            $char = $mszReaders[$i];
            if ($char != "\x00") {
                $buf .= $char;
            } else {
                if ($buf != "") {
                    $return[] = new Reader($this, $buf);
                    $buf = "";
                } else {
                    break;
                }
            }
        }
        $ffi->SCardFreeMemory($this->hContext->cdata, $mszReaders);
        return $return;
    }

    public function close()
    {
        $ffi = $this->ffi;
        $ret = $ffi->SCardReleaseContext($this->hContext->cdata);
        if ($ret != 0) {
            $errstr = $ffi->pcsc_stringify_error($ret);
            for ($i = 0; $errstr[$i] != "\x00"; $i++) {
                echo $errstr[$i];
            }
            echo PHP_EOL;
            throw new Exception("SCardReleaseContext failed");
        }
    }

    public function getFFI()
    {
        return $this->ffi;
    }

    public function getHContext()
    {
        return $this->hContext;
    }
}
