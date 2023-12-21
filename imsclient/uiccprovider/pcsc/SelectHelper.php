<?php

namespace imsclient\uiccprovider\pcsc;

use imsclient\uiccprovider\pcsc\apdu\APDUResponse;
use imsclient\uiccprovider\pcsc\apdu\SW;
use imsclient\uiccprovider\pcsc\apdu\GetResponseAPDU;
use imsclient\uiccprovider\pcsc\apdu\SelectAPDU;
use imsclient\uiccprovider\pcsc\apdu\SelectIDAPDU;
use imsclient\uiccprovider\pcsc\apdu\SelectNameAPDU;
use imsclient\uiccprovider\pcsc\apdu\SelectPathAPDU;
use imsclient\uiccprovider\pcsc\struct\FCI;
use imsclient\uiccprovider\pcsc\pcsclite\Card;
use Exception;

abstract class SelectHelper
{
    static private function run(Card $card, SelectAPDU $apdu): FCI
    {
        $res = APDUResponse::unpack($card->transmit($apdu));
        if ($res->sw1 !== SW::SW1_LAST) {
            throw new Exception("Failed to select: " . var_dump($res));
        }
        $res = APDUResponse::unpack($card->transmit(new GetResponseAPDU($res->sw2)));
        if ($res->sw1 !== SW::SW1_OK) {
            throw new Exception("Failed to read FCI: " . var_dump($res));
        }
        $fci = FCI::unpack($res->data);
        return $fci;
    }

    static public function id($card, $id)
    {
        return self::run($card, new SelectIDAPDU($id, true));
    }

    static public function path($card, $path)
    {
        return self::run($card, new SelectPathAPDU($path, true));
    }

    static public function name($card, $name)
    {
        return self::run($card, new SelectNameAPDU($name, true));
    }
}
