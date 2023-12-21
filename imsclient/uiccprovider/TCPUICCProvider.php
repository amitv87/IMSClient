<?php

namespace imsclient\uiccprovider;

use imsclient\network\EventSocket;

class TCPUICCProvider implements UICCProvider
{
    private $server_addr;
    private $server_port;
    private $iccid = "";
    private $imsi = "";
    private $mcc = "";
    private $mnc = "";
    private $smsc = "";

    public function __construct($iccid = null, $addr = "127.0.0.1", $port = 3516)
    {
        $this->server_addr = $addr;
        $this->server_port = $port;
        $ret = $this->tramsmit(["uiccinfo"]);
        $this->iccid = $ret['iccid'];
        $this->imsi = $ret['imsi'];
        $this->mcc = $ret['mcc'];
        $this->mnc = $ret['mnc'];
        $this->smsc = $ret['smsc'];
    }

    private function getConnection()
    {
        $socket = new EventSocket(EventSocket::TCP);
        $socket->connect($this->server_addr, $this->server_port);
        return $socket;
    }

    private function tramsmit($rpc)
    {
        $socket = $this->getConnection();
        $socket->writeLine(json_encode($rpc));
        $ret = $socket->readLine();
        $socket->close();
        $ret = json_decode($ret, true);
        return $ret;
    }

    public function auth($rand, $autn)
    {
        $ret = $this->tramsmit(["uiccauth", base64_encode($rand), base64_encode($autn)]);
        return [
            'res' => base64_decode($ret['res']),
            'ck' => base64_decode($ret['ck']),
            'ik' => base64_decode($ret['ik']),
        ];
    }

    public function getICCID()
    {
        return $this->iccid;
    }

    public function getIMSI()
    {
        return $this->imsi;
    }

    public function getMCC()
    {
        return $this->mcc;
    }

    public function getMNC()
    {
        return $this->mnc;
    }

    public function getSMSC()
    {
        return $this->smsc;
    }

    public function getIMS()
    {
        return null;
    }
}
