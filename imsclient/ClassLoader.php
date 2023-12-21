<?php

namespace imsclient;

use ReflectionClass;
use ReflectionMethod;

class ClassLoader
{
    private $lookup = [];

    public function addPath($path)
    {
        foreach ($this->lookup as $p) {
            if ($p === $path) {
                return;
            }
        }
        $this->lookup[] = $path;
    }

    public function delPath($path)
    {
        foreach ($this->lookup as $i => $p) {
            if ($p === $path) {
                unset($this->lookup[$i]);
            }
        }
    }

    public function register($prepend = false)
    {
        spl_autoload_register([$this, "loadClass"], true, $prepend);
    }

    public function loadClass($name)
    {
        $path = $this->findClass($name);
        if (!($path === false)) {
            require_once($path);
        }
        if (!class_exists($name, false) && !interface_exists($name, false) && !trait_exists($name, false)) {
            return false;
        }
        if (method_exists($name, '__onLoad')) {
            $method = new ReflectionMethod($name, '__onLoad');
            if ($method->isPublic() && $method->isStatic()) {
                $reflection = new ReflectionClass($name);
                if (!$reflection->isInterface() && !$reflection->isTrait()) {
                    $name::__onLoad();
                }
            }
        }
        return true;
    }

    public function findClass($name)
    {
        $components = explode('\\', $name);
        $baseName = implode(DIRECTORY_SEPARATOR, $components);
        foreach ($this->lookup as $path) {
            $file = $path . DIRECTORY_SEPARATOR . $baseName . ".php";
            if (file_exists($file)) {
                return $file;
            }
        }
        return false;
    }
}
