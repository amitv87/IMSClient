<?php

namespace imsclient\protocol\ims\header;

interface HeaderInterface
{
    static public function getName();
    static public function fromString($str);
    public function toString();
}
