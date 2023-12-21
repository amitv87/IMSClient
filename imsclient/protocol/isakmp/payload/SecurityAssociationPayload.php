<?php

namespace imsclient\protocol\isakmp\payload;

use imsclient\datastruct\StringStream;

class SecurityAssociationPayload extends GenericPayload
{
    static protected $tag = 33;

    /** @var ProposalPayload[] */
    public $proposal = [];

    public function __construct(array $proposal = [])
    {
        $this->proposal = $proposal;
    }

    protected function parse()
    {
        $stream = new StringStream($this->_payload);

        $this->proposal = GenericPayload::parseChain(ProposalPayload::getTag(), $stream);
    }

    protected function generate(): string
    {
        $bytes = "";
        GenericPayload::prepareChain($this->proposal);
        foreach ($this->proposal as $key => $proposal) {
            $proposal->number = $key + 1;
            $bytes .= $proposal->pack();
        }
        return $bytes;
    }
}
