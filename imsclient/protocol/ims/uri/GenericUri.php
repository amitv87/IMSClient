<?php

namespace imsclient\protocol\ims\uri;

use Exception;
use ReflectionClass;

class GenericUri
{
    static protected $_classmap = [];
    static protected $_proto;
    protected $_value;

    static public function getProto()
    {
        return static::$_proto;
    }

    protected function generate()
    {
        throw new Exception("generate unimpl");
    }

    protected function parse()
    {
    }

    public function getValue()
    {
        $this->generate();
        return $this->_value;
    }

    public function __toString()
    {
        return $this->toString();
    }

    public function toString()
    {
        return static::getProto() . ":" . $this->getValue();
    }

    static public function fromString($str): GenericUri
    {
        $exped = explode(":", $str, 2);
        $proto = $exped[0];
        $value =  ltrim($exped[1], " ");

        $classname = self::class;
        if (isset(static::$_classmap[$proto])) {
            $classname = static::$_classmap[$proto];
        }
        $reflection = new ReflectionClass($classname);
        $instance = $reflection->newInstanceWithoutConstructor();

        $instance::$_proto = $proto;
        $instance->_value = $value;
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
        $name = $current::getProto();
        if ($name) {
            static::$_classmap[$name] = $current;
        }
    }
}
