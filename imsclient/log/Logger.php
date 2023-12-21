<?php

namespace imsclient\log;

abstract class Logger
{
    static private $logfile = "./imsclient.log";

    public static function debug($log, $level = 1, $color = TextFormat::RESET)
    {
        self::show('DEBUG', $log, $color);
    }

    public static function info($log, $color = TextFormat::WHITE)
    {
        self::show('INFO', $log, $color);
    }

    public static function success($log, $color = TextFormat::GREEN)
    {
        self::show('SUCC', $log, $color);
    }

    public static function fail($log, $color = TextFormat::RED)
    {
        self::show('FAIL', $log, $color);
    }

    public static function warning($log, $color = TextFormat::YELLOW)
    {
        self::show('WARN', $log, $color);
    }

    public static function alert($log, $color = TextFormat::RED)
    {
        self::show('ALER', $log, $color);
    }

    private static function show($pre, $log, $color)
    {
        $class = @end(explode('\\', debug_backtrace()[2]['class']));
        $log = TextFormat::AQUA . date('[Y-m-d G:i:s]') . "({$class}): " . $color . "[$pre] $log" . TextFormat::RESET . PHP_EOL;
        echo $log;
        self::writeRAW($pre, $log);
    }

    private static function writeRAW($pre, $log)
    {
        if ($pre != 'DEBUG') {
            file_put_contents(self::$logfile, TextFormat::clean($log), FILE_APPEND);
        }
    }
}
