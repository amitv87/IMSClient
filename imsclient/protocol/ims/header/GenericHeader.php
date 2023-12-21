<?php

namespace imsclient\protocol\ims\header;

use Exception;
use ReflectionClass;

class GenericHeader implements HeaderInterface
{
    static protected $_classmap = [];
    static protected $_name;
    protected $_value;

    static public function getName()
    {
        return static::$_name;
    }

    public function getValue()
    {
        return $this->_value;
    }

    protected function generate()
    {
        throw new Exception("generate unimpl");
    }

    protected function parse()
    {
    }

    public function __toString()
    {
        return $this->toString();
    }

    public function toString()
    {
        if ($this->_value == "")
            $this->generate();
        return static::getName() . ": " . $this->_value;
    }

    static public function fromString($str): GenericHeader
    {
        $exped = explode(":", $str, 2);
        $name = strtolower($exped[0]);
        $value = ltrim($exped[1], " ");

        $classname = self::class;
        if (isset(static::$_classmap[$name])) {
            $classname = static::$_classmap[$name];
        }
        $reflection = new ReflectionClass($classname);
        $instance = $reflection->newInstanceWithoutConstructor();

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
        $name = $current::getName();
        if ($name) {
            static::$_classmap[strtolower($name)] = $current;
        }
    }
}
