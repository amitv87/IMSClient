<?php

namespace imsclient\api;

use imsclient\IMSClient;

class APIService
{
    public function sms($to, $data, $smsc = null)
    {
        if($smsc == null) {
            $smsc = IMSClient::getInstance()->getIdentity()->getCardProvider()->getSMSC();
        }
        return IMSClient::getInstance()->sendSMS($smsc, $to, $data);
    }
}
