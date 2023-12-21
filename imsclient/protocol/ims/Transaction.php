<?php

namespace imsclient\protocol\ims;

class Transaction
{
    public $tag;
    public $callid;
    public $sessionid;
    public $branch;

    public function __construct()
    {
        $this->tag = static::generateTag();
        $this->callid = static::generateCallID();
        $this->sessionid = static::generateSessionID();
        $this->branch = static::generateBranch();
    }

    static private function generateCallID()
    {
        return static::generateRandomString(24);
    }

    static private function generateSessionID()
    {
        return bin2hex(static::generateRandomString(16));
    }

    static private function generateBranch()
    {
        return "z9hG4bK" . static::generateRandomString(15);
    }

    static private function generateTag()
    {
        return static::generateRandomString(10);
    }

    static private function generateRandomString($length = 10)
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }
}
