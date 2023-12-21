<?php

namespace imsclient\protocol\ims\header;

class ViaHeader extends GenericHeader
{
    static protected $_name = "Via";

    public $protocol;
    public $ipaddr;
    public $port;
    public $branch;

    public function __construct($protocol, $ipaddr, $port, $branch)
    {
        if (strstr($ipaddr, ':') !== false) {
            $ipaddr = '[' . $ipaddr . ']';
        }
        $this->protocol = strtoupper($protocol);
        $this->ipaddr = $ipaddr;
        $this->port = $port;
        $this->branch = $branch;
    }

    protected function generate()
    {
        $this->_value = "SIP/2.0/{$this->protocol} {$this->ipaddr}:{$this->port};branch={$this->branch};rport";
    }
}
