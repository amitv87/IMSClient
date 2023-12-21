<?php

namespace imsclient\protocol\gsm\tpdu;

use Exception;
use imsclient\exception\DataParseException;
use imsclient\datastruct\StringStream;
use ReflectionClass;

class TPUserDataGenericHeader
{
    static protected $_classmap = [];
    /** @var int */
    static protected $iei = null;
    /** @var int */
    static protected $length = null;

    private $_value;

    public function getLength()
    {
        return static::$length;
    }

    protected function generate(): string
    {
        throw new Exception("generate unimpl");
        return "";
    }

    protected function parse(StringStream $stream)
    {
        $this->_value = $stream->read(static::$length);
    }

    public function pack()
    {
        $data = pack('C', static::$iei);
        $data .= pack('C', static::$length);
        $data .= $this->generate();
        return $data;
    }

    static public function unpack(StringStream $stream): static
    {
        $iei = $stream->readU8();

        $class_name = self::class;
        if (isset(static::$_classmap[$iei])) {
            $class_name = static::$_classmap[$iei];
        }
        $reflection = new ReflectionClass($class_name);
        $instance = $reflection->newInstanceWithoutConstructor();

        $length = $stream->readU8();
        if ($class_name == self::class) {
            self::$iei = $iei;
            self::$length = $length;
        } else {
            if ($length != $instance->getLength()) {
                throw new DataParseException("TPUserDataHeader length mismatch [{$class_name}] " . $length . " != " . $instance->getLength());
            }
        }

        $instance->parse($stream);
        return $instance;
    }

    static public function __onLoad()
    {
        $current = get_called_class();
        if ($current === self::class) {
            $files = glob(__DIR__ . DIRECTORY_SEPARATOR . "*.php");
            foreach ($files as $f) {
                $classname = __NAMESPACE__ . '\\' .  basename($f, ".php");
                class_exists($classname, true);
            }
            return;
        }
        $iei = $current::$iei;
        if ($iei !== null) {
            static::$_classmap[$iei] = $current;
        }
    }
}
