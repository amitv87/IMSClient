<?php

namespace imsclient\protocol\isakmp\packet;

use imsclient\protocol\isakmp\payload\EncrypedAndAuthenticatedPayload;
use imsclient\protocol\isakmp\payload\GenericPayload;
use imsclient\protocol\isakmp\Transaction;
use imsclient\datastruct\StringStream;
use ReflectionClass;

class GenericPacket
{
    protected static $_exchangetype;

    public $spi_initiator;       // u64be
    public $spi_responder;       // u64be

    public $nextpayload;        // u8

    /* Byte Version */
    public $version_majVer;     // bit[4-7]
    public $version_minVer;     // bit[0-3]
    /* End Byte Version */

    public $exchangetype;       // u8

    /* Byte Flags */
    public $flags_initiator;    // bit[3]
    public $flags_version;      // bit[4]
    public $flags_response;     // bit[5]
    /* End Byte Flags */

    public $messageid;          // u32be
    protected $length;             // u32be

    /** @var GenericPayload[] */
    public $payload = [];

    protected $_payload;

    /** @var Transaction */
    protected $transaction;

    protected $_rawpacket;

    public function __construct(Transaction $t)
    {
        $this->transaction = $t;
        $this->spi_initiator = $t->spi_initiator;
        $this->spi_responder = $t->spi_responder;
        $this->version_majVer = 2;
        $this->version_minVer = 0;
        $this->flags_initiator = 1;
        $this->flags_version = 0;
        $this->flags_response = 0;
        $this->messageid = $t->message_id;
        $this->exchangetype = static::$_exchangetype;
        $this->default();
    }

    protected function default()
    {
    }

    static public function unpack(Transaction $t, string $data): static
    {
        $stream = new StringStream($data);
        $reflection = new ReflectionClass(static::class);
        $instance = $reflection->newInstanceWithoutConstructor();

        $instance->_rawpacket = $data;

        $instance->transaction = $t;

        $instance->spi_initiator = $stream->readU64BE();
        $instance->spi_responder = $stream->readU64BE();

        $instance->nextpayload = $stream->readU8();

        if ($instance->nextpayload == EncrypedAndAuthenticatedPayload::getTag()) {
            if (get_called_class() != EncryptedPacket::class) {
                return EncryptedPacket::unpack($t, $data);
            }
        }

        $byte = $stream->readU8();
        $instance->version_majVer = ($byte & 0xF0) >> 4;
        $instance->version_minVer = ($byte & 0x0F);

        $instance->exchangetype = $stream->readU8();

        $byte = $stream->readU8();
        $instance->flags_initiator = ($byte & 0x08) >> 3;
        $instance->flags_version = ($byte & 0x10) >> 4;
        $instance->flags_response = ($byte & 0x20) >> 5;

        $instance->messageid = $stream->readU32BE();
        $instance->length = $stream->readU32BE();

        $instance->_payload = $stream->read($instance->length - 28); // -28 for the header

        $stream = new StringStream($instance->_payload);
        $instance->payload = GenericPayload::parseChain($instance->nextpayload, $stream);

        return $instance;
    }

    public function pack()
    {
        $this->nextpayload = GenericPayload::prepareChain($this->payload);
        $this->_payload = "";
        foreach ($this->payload as $payload) {
            $this->_payload .= $payload->pack();
        }
        $this->length = strlen($this->_payload) + 28; // +28 for the header
        $bindata = "";
        $bindata .= pack('J', $this->spi_initiator);
        $bindata .= pack('J', $this->spi_responder);
        $bindata .= pack('C', $this->nextpayload);
        $bindata .= pack('C', ($this->version_majVer << 4) | $this->version_minVer);
        $bindata .= pack('C', $this->exchangetype);
        $bindata .= pack('C', ($this->flags_initiator << 3) | ($this->flags_version << 4) | ($this->flags_response << 5));
        $bindata .= pack('N', $this->messageid);
        $bindata .= pack('N', $this->length);
        $bindata .= $this->_payload;
        return $bindata;
    }

    const EXCHANGE_TYPE_IKE_SA_INIT = 34;
    const EXCHANGE_TYPE_IKE_AUTH = 35;
    const EXCHANGE_INFORMATIONAL = 37;
}
