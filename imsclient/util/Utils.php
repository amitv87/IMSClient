<?php

namespace imsclient\util;

use imsclient\log\Logger;

class Utils
{
    static public function passthru($cmd)
    {
        Logger::debug($cmd);
        passthru($cmd);
    }

    static public function clone($obj)
    {
        return unserialize(serialize($obj));
    }
}
