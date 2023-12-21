<?php

namespace imsclient\protocol\ims\packet;

use imsclient\network\EventSocket;
use imsclient\protocol\ims\header\ContentLengthHeader;
use imsclient\protocol\ims\header\HeaderInterface;

class GenericPacket
{
    /** @var IMSSocket */
    protected $income_socket;
    /** @var HeaderInterface */
    protected $headers = [];
    protected $body;

    public function getHeader($name)
    {
        $name = strtolower($name);
        if (isset($this->headers[$name][0])) {
            return $this->headers[$name][0];
        }
        return null;
    }

    public function getHeaders($name)
    {
        $name = strtolower($name);
        if (isset($this->headers[$name])) {
            return $this->headers[$name];
        }
        return [];
    }

    public function addHeader(HeaderInterface $h)
    {
        $name = strtolower($h::getName());
        $this->headers[$name][] = $h;
    }

    public function appendBody($body)
    {
        $this->body .= $body;
    }

    public function getBody()
    {
        return $this->body;
    }

    public function toString()
    {
        $this->headers[ContentLengthHeader::getName()] = [new ContentLengthHeader(strlen($this->body))];
        $str = "";
        foreach ($this->headers as $header) {
            foreach ($header as $h) {
                $str .= $h->toString() . "\r\n";
            }
        }
        $str .= "\r\n";
        $str .= $this->body;
        return $str;
    }

    public function __toString()
    {
        return $this->toString();
    }

    public function setIncomeSocket(EventSocket $socket)
    {
        $this->income_socket = $socket;
    }

    public function getIncomeSocket()
    {
        return $this->income_socket;
    }

    public function __sleep()
    {
        return ['headers', 'body'];
    }
}
