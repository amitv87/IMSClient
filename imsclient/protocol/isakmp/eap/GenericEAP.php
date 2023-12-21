<?php

namespace imsclient\protocol\isakmp\eap;

use Exception;
use imsclient\datastruct\StringStream;
use ReflectionClass;

class GenericEAP
{
    protected static $_classmap;
    protected static $_type;
    protected static $_code;

    public $code;   // u8
    public $id;     // u8
    public $length; // u16be
    public $type = null;   // u8

    protected $_payload;

    public function __construct($id)
    {
        $this->id = $id;
        $this->type = static::$_type;
    }

    protected function parse()
    {
    }

    protected function generate(): string
    {
        throw new Exception("generate unimpl");
        return "";
    }

    public function pack()
    {
        $this->_payload = $this->generate();
        $this->length = strlen($this->_payload) + 4;
        if ($this->type !== null) {
            $this->length += 1;
        }
        $bytes = "";
        $bytes .= pack('C', $this->code);
        $bytes .= pack('C', $this->id);
        $bytes .= pack('n', $this->length);
        if ($this->type !== null) {
            $bytes .= pack('C', $this->type);
        }
        $bytes .= $this->_payload;
        return $bytes;
    }

    static public function unpack($data)
    {
        $stream = new StringStream($data);
        $code = $stream->readU8();
        $id = $stream->readU8();
        $length = $stream->readU16BE();
        $classname = self::class;
        $type = null;
        if ($code !== self::CODE_SUCCESS) {
            $type = $stream->readU8();
            if (isset(static::$_classmap[$type][$code])) {
                $classname = static::$_classmap[$type][$code];
            }
        }
        $reflection = new ReflectionClass($classname);
        $instance = $reflection->newInstanceWithoutConstructor();
        $instance->code = $code;
        $instance->id = $id;
        $instance->length = $length;
        $instance->type = $type;
        $instance->_payload = $stream->readAll();
        $instance->parse();
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
        if (is_numeric(static::$_type)) {
            static::$_classmap[static::$_type][static::$_code] = $current;
        }
    }

    static public function getType()
    {
        return static::$_type;
    }

    static public function getCode()
    {
        return static::$_code;
    }

    const CODE_REQUEST = 1;
    const CODE_RESPONSE = 2;
    const CODE_SUCCESS = 3;
}
