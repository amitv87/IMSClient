<?php

namespace imsclient\protocol\ims\packet;

use imsclient\protocol\ims\header\AllowHeader;
use imsclient\protocol\ims\header\CallIDHeader;
use imsclient\protocol\ims\header\CSeqHeader;
use imsclient\protocol\ims\header\FromHeader;
use imsclient\protocol\ims\header\PAccessNetworkInfoHeader;
use imsclient\protocol\ims\header\StatusLine;
use imsclient\protocol\ims\header\SupportedHeader;
use imsclient\protocol\ims\header\ToHeader;
use imsclient\protocol\ims\header\UserAgentHeader;
use imsclient\protocol\ims\header\ViaHeader;

class StatusPacket extends GenericPacket
{
    const reason = [
        200 => "OK",
        400 => "Bad Request",
        401 => "Unauthorized",
        403 => "Forbidden",
        404 => "Not Found",
        405 => "Method Not Allowed",
    ];

    public function __construct(RequestPacket $req, $code = 200)
    {
        $this->addHeader(new StatusLine($code, static::reason[$code]));
        $this->addHeader($req->getHeader(ViaHeader::getName()));
        $this->addHeader($req->getHeader(ToHeader::getName()));
        $this->addHeader($req->getHeader(FromHeader::getName()));
        $this->addHeader($req->getHeader(CallIDHeader::getName()));
        $this->addHeader($req->getHeader(CSeqHeader::getName()));
        $this->addHeader(new AllowHeader());
        $this->addHeader(new SupportedHeader());
        $this->addHeader(new UserAgentHeader());
        $this->addHeader(new PAccessNetworkInfoHeader());
    }
}
