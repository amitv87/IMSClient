<?php

namespace imsclient\protocol\ims;

use imsclient\exception\DataParseException;
use imsclient\protocol\ims\header\ContentLengthHeader;
use imsclient\protocol\ims\header\GenericHeader;
use imsclient\protocol\ims\header\RequestLine;
use imsclient\protocol\ims\header\StatusLine;
use imsclient\protocol\ims\packet\RequestPacket;
use imsclient\protocol\ims\packet\StatusPacket;
use imsclient\protocol\ims\packet\GenericPacket;
use ReflectionClass;

class PacketParser
{
    private const STATE_START = 0;
    private const STATE_HEADER = 1;
    private const STATE_BODY = 2;

    /** @var string */
    private $buffer = "";
    /** @var int */
    private $state = self::STATE_START;
    /** @var int */
    private $bodylength = 0;
    /** @var GenericPacket */
    private $current_packet = null;
    /** @var GenericPacket[] */
    private $packets = [];

    public function getPacket(): ?GenericPacket
    {
        return array_shift($this->packets);
    }

    private function finishOne()
    {
        $this->packets[] = $this->current_packet;
        $this->current_packet = null;
        $this->state = self::STATE_START;
        $this->bodylength = 0;
    }

    public function read($data)
    {
        $this->buffer .= $data;
        $this->buffer = str_replace(",\r\n", ",", $this->buffer);
        $this->parse();
    }

    private function shiftLine()
    {
        $exp = explode("\r\n", $this->buffer, 2);
        if (count($exp) === 2) {
            $this->buffer = $exp[1];
            return $exp[0];
        } else {
            return false;
        }
    }

    private function parse()
    {
        while (strlen($this->buffer) > 0) {
            if ($this->state !== self::STATE_BODY) {
                $line = $this->shiftLine();
                if ($line === false) {
                    break;
                }
                switch ($this->state) {
                    case self::STATE_START:
                        if (substr($line, 0, 4) === "SIP/") {
                            $obj = StatusLine::fromString($line);
                            $reflection = new ReflectionClass(StatusPacket::class);
                            $this->current_packet = $reflection->newInstanceWithoutConstructor();
                        } else {
                            $obj = RequestLine::fromString($line);
                            $reflection = new ReflectionClass(RequestPacket::class);
                            $this->current_packet = $reflection->newInstanceWithoutConstructor();
                        }
                        $this->current_packet->addHeader($obj);
                        $this->state = self::STATE_HEADER;
                        break;
                    case self::STATE_HEADER:
                        if (strlen($line) === 0) {
                            if (!($cl = $this->current_packet->getHeader(ContentLengthHeader::getName()))) {
                                throw new DataParseException("No Content-Length header");
                            }
                            $this->bodylength = $cl->getValue();
                            if ($this->bodylength === 0) {
                                $this->finishOne();
                                break;
                            }
                            $this->state = self::STATE_BODY;
                            break;
                        }
                        $obj = GenericHeader::fromString($line);
                        $this->current_packet->addHeader($obj);
                        break;
                }
            } else {
                $copylen = min($this->bodylength, strlen($this->buffer));
                $this->current_packet->appendBody(substr($this->buffer, 0, $copylen));
                $this->buffer = substr($this->buffer, $copylen);
                $this->bodylength -= $copylen;
                if ($this->bodylength === 0) {
                    $this->finishOne();
                }
            }
        }
    }
}
