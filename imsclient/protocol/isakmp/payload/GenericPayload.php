<?php

namespace imsclient\protocol\isakmp\payload;

use Exception;
use imsclient\protocol\isakmp\Transaction;
use imsclient\datastruct\StringStream;
use ReflectionClass;

class GenericPayload
{
    const TYPE_NONE = 0;

    static protected $_classmap = [];

    static protected $tag;

    public $nextpayload = 0; // u8

    /* Byte Flags */
    public $flag_critical = 0; // bit[7]
    /* End Byte Flags */

    private $payload_length = 0; // u16be

    protected $_payload = "";

    /** @var Transaction */
    protected $transaction;

    static public function getTag()
    {
        return static::$tag;
    }

    protected function parse()
    {
    }

    protected function generate(): string
    {
        throw new Exception("generate unimpl");
        return "";
    }

    public function unpack(StringStream $stream)
    {
        $this->nextpayload = $stream->readU8();

        $byte = $stream->readU8();
        $this->flag_critical = ($byte & 0x80) >> 7;

        $this->payload_length = $stream->readU16BE();

        $this->_payload = $stream->read($this->payload_length - 4); // -4 for the header

        $this->parse();
    }

    public function pack()
    {
        $this->_payload = $this->generate();
        $this->payload_length = strlen($this->_payload) + 4; // +4 for the header

        $packed = '';
        $packed .= pack('C', $this->nextpayload);
        $byte = $this->flag_critical << 7;
        $packed .= pack('C', $byte);
        $packed .= pack('n', $this->payload_length);
        $packed .= $this->_payload;

        return $packed;
    }

    public function getRawPayload()
    {
        return $this->_payload;
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
        $tag = $current::getTag();
        if (is_numeric($tag)) {
            static::$_classmap[$tag] = $current;
        }
    }

    static public function getInstance($tag)
    {
        $classname = self::class;
        if (isset(static::$_classmap[$tag])) {
            $classname = static::$_classmap[$tag];
        }
        $reflection = new ReflectionClass($classname);
        $instance = $reflection->newInstanceWithoutConstructor();
        return $instance;
    }

    static public function parseChain($initial_type, StringStream $stream)
    {
        $ret = [];
        $type = $initial_type;
        do {
            $instance = self::getInstance($type);
            $instance->unpack($stream);
            $ret[] = $instance;
            if ($instance->nextpayload === self::TYPE_NONE) {
                break;
            }
            if ($instance instanceof EncrypedAndAuthenticatedPayload) {
                break;
            }
            $type = $instance->nextpayload;
        } while (1);
        return $ret;
    }

    static public function prepareChain($payloads)
    {
        $count = count($payloads);
        if ($count == 0) {
            return self::TYPE_NONE;
        }
        if (!($payloads[$count - 1] instanceof EncrypedAndAuthenticatedPayload)) {
            $payloads[$count - 1]->nextpayload = self::TYPE_NONE;
        }
        for ($i = $count - 2; $i >= 0; $i--) {
            $payloads[$i]->nextpayload = $payloads[$i + 1]->getTag();
        }
        return $payloads[0]::$tag;
    }

    static public function findAll($arr): array
    {
        $ret = [];
        foreach ($arr as $obj) {
            if ($obj::$tag == static::$tag) {
                $ret[] = $obj;
            }
        }
        return $ret;
    }

    static public function findOne($arr): static
    {
        return static::findAll($arr)[0] ?? null;
    }
}
