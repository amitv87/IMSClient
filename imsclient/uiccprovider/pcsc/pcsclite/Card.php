<?php

namespace imsclient\uiccprovider\pcsc\pcsclite;

use Exception;
use \FFI;
use \FFI\CData;

class Card
{
    private Reader $reader;
    private CData $hCard;

    private $state;
    private $protocol;
    private $atr;

    public function __construct(Reader $reader, CData $hCard)
    {
        $this->reader = $reader;
        $this->hCard = $hCard;

        $ffi = $this->reader->getPCSC()->getFFI();
        $dwState = $ffi->new("DWORD");
        $dwProtocol = $ffi->new("DWORD");
        $pbAtr = $ffi->new("BYTE[" . C::MAX_ATR_SIZE . "]");
        $cbAtrLen = $ffi->new("DWORD");
        $cbAtrLen->cdata = FFI::sizeof($pbAtr);
        $ret = $ffi->SCardStatus($this->hCard->cdata, null, null, FFI::addr($dwState), FFI::addr($dwProtocol), $pbAtr, FFI::addr($cbAtrLen));
        if ($ret != 0) {
            $errstr = $ffi->pcsc_stringify_error($ret);
            for ($i = 0; $errstr[$i] != "\x00"; $i++) {
                echo $errstr[$i];
            }
            echo PHP_EOL;
            throw new Exception("SCardStatus failed");
        }
        $this->state = $dwState->cdata;
        $this->protocol = $dwProtocol->cdata;
        $this->atr = "";
        for ($i = 0; $i < $cbAtrLen->cdata; $i++) {
            $this->atr .= pack('C', $pbAtr[$i]);
        }
    }

    private function transmitT0($data)
    {
        $ffi = $this->reader->getPCSC()->getFFI();
        $pbSendBuffer = $ffi->new("BYTE[" . strlen($data) . "]");
        FFI::memcpy($pbSendBuffer, $data, strlen($data));
        $dwSendLength = $ffi->new("DWORD");
        $dwSendLength = strlen($data);
        $pioRecvPci = $ffi->new("SCARD_IO_REQUEST");
        $pbRecvBuffer = $ffi->new("BYTE[" . C::MAX_BUFFER_SIZE . "]");
        $dwRecvLength = $ffi->new("DWORD");
        $dwRecvLength->cdata = FFI::sizeof($pbRecvBuffer);
        $ret = $ffi->SCardTransmit($this->hCard->cdata, FFI::addr($ffi->g_rgSCardT0Pci), $pbSendBuffer, $dwSendLength, FFI::addr($pioRecvPci), $pbRecvBuffer, FFI::addr($dwRecvLength));
        if ($ret != 0) {
            $errstr = $ffi->pcsc_stringify_error($ret);
            for ($i = 0; $errstr[$i] != "\x00"; $i++) {
                echo $errstr[$i];
            }
            echo PHP_EOL;
            throw new Exception("SCardTransmit failed");
        }
        $response = "";
        for ($i = 0; $i < $dwRecvLength->cdata; $i++) {
            $response .= pack('C', $pbRecvBuffer[$i]);
        }
        return $response;
    }

    public function transmit(string $data)
    {
        switch ($this->protocol) {
            case C::SCARD_PROTOCOL_T0:
                return $this->transmitT0($data);
        }
    }

    public function disconnect()
    {
        $ffi = $this->reader->getPCSC()->getFFI();

        $ret = $ffi->SCardDisconnect($this->hCard->cdata, C::SCARD_LEAVE_CARD);
        if ($ret != 0) {
            $errstr = $ffi->pcsc_stringify_error($ret);
            for ($i = 0; $errstr[$i] != "\x00"; $i++) {
                echo $errstr[$i];
            }
            echo PHP_EOL;
            throw new Exception("SCardDisconnect failed");
        }
    }

    public function getState()
    {
        return $this->state;
    }

    public function getProtocol()
    {
        return $this->protocol;
    }

    public function getAtr()
    {
        return $this->atr;
    }

    const PROTOCOL_T0 = C::SCARD_PROTOCOL_T0;
    const PROTOCOL_T1 = C::SCARD_PROTOCOL_T1;
}
