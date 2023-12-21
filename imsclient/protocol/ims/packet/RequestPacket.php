<?php

namespace imsclient\protocol\ims\packet;

use imsclient\Identity;
use imsclient\protocol\ims\Transaction;

class RequestPacket extends GenericPacket
{
    /** @var Transaction */
    protected $ts;
    /** @var Identity */
    protected $identity;

    public function __construct(Transaction $ts, Identity $identity)
    {
        $this->ts = $ts;
        $this->identity = $identity;
        $this->default();
    }

    protected function default()
    {
    }
}
