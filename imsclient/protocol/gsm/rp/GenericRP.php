<?php

namespace imsclient\protocol\gsm\rp;

use imsclient\exception\DataParseException;
use imsclient\datastruct\StringStream;
use ReflectionClass;

class GenericRP
{
    static protected $_classmap = [];
    /** @var int */
    static protected $type;
    /** @var int */
    public $reference;

    public function __construct($reference)
    {
        $this->reference = $reference;
    }

    protected function generate(): string
    {
        throw new DataParseException("generate unimpl");
        return "";
    }

    protected function parse(StringStream $stream)
    {
        throw new DataParseException("parse unimpl");
    }

    public function pack()
    {
        $data = pack('C', static::$type);
        $data .= pack('C', $this->reference);
        $data .= $this->generate();
        return $data;
    }

    static public function unpack(string $data): static
    {
        $stream = new StringStream($data);

        $type = $stream->readU8();
        if (!isset(static::$_classmap[$type])) {
            throw new DataParseException("unknown RP type: " . bin2hex($data));
        }

        $reflection = new ReflectionClass(static::$_classmap[$type]);
        $instance = $reflection->newInstanceWithoutConstructor();

        $instance->reference = $stream->readU8();

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
        $type = $current::$type;
        if ($type !== null) {
            static::$_classmap[$type] = $current;
        }
    }
}
