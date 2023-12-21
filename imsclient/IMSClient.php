<?php

namespace imsclient;

use imsclient\api\APIServer;
use imsclient\exception\FatalException;
use imsclient\exception\DataParseException;
use imsclient\log\Logger;
use imsclient\network\EventSocket;
use imsclient\network\IMSSocketPool;
use imsclient\network\IPSecHelper;
use imsclient\protocol\gsm\rp\GenericRP;
use imsclient\protocol\gsm\rp\RPAckMSToNetwork;
use imsclient\protocol\gsm\rp\RPAckNetworkToMS;
use imsclient\protocol\gsm\rp\RPAddress;
use imsclient\protocol\gsm\rp\RPDataMSToNetwork;
use imsclient\protocol\gsm\rp\RPDataNetworkToMS;
use imsclient\protocol\gsm\tpdu\SMSDeliver;
use imsclient\protocol\gsm\tpdu\SMSSubmit;
use imsclient\protocol\gsm\tpdu\TPAddress;
use imsclient\protocol\gsm\tpdu\TPUserDataGenericHeader;
use imsclient\protocol\ims\header\BasicSecurityHeaderParam;
use imsclient\protocol\ims\header\CallIDHeader;
use imsclient\protocol\ims\header\CSeqHeader;
use imsclient\protocol\ims\header\GenericHeader;
use imsclient\protocol\ims\header\InReplyToHeader;
use imsclient\protocol\ims\header\PAssertedIdentityHeader;
use imsclient\protocol\ims\header\RequestLine;
use imsclient\protocol\ims\header\StatusLine;
use imsclient\protocol\ims\packet\GenericPacket;
use imsclient\protocol\ims\packet\MessagePacket;
use imsclient\protocol\ims\packet\RequestPacket;
use imsclient\protocol\ims\packet\StatusPacket;
use imsclient\protocol\ims\RegisterSequence;
use imsclient\protocol\ims\Transaction;
use imsclient\protocol\ims\uri\GenericUri;
use imsclient\protocol\ims\uri\TelUri;
use imsclient\protocol\isakmp\eap\GenericEAP;
use imsclient\protocol\isakmp\payload\GenericPayload;
use imsclient\uiccprovider\PCSCUICCProvider;
use Throwable;

class IMSClient
{
    private $shutdown = false;
    /** @var self */
    private static $instance = null;
    /** @var SWuClient */
    private $swu;
    /** @var IMSSocketPool */
    private $socket;
    /** @var Identity */
    private $identity;
    /** @var APIServer */
    private $api;

    private $status_packet_handler = [];
    private $rp_reference = 1;

    public function __construct()
    {
        self::$instance = $this;
        register_shutdown_function([$this, 'onExit']);
        pcntl_signal(SIGINT, [$this, 'onExit']);
        $this->autoLoad();
    }

    private function autoLoad()
    {
        GenericRP::__onLoad();
        TPUserDataGenericHeader::__onLoad();
        GenericHeader::__onLoad();
        GenericUri::__onLoad();
        GenericEAP::__onLoad();
        GenericPayload::__onLoad();
    }

    public function run()
    {
        try {
            Logger::success(ConstVar::codename . " " . ConstVar::codever);
            $this->identity = new Identity(new PCSCUICCProvider(), "35665642-130527-6");
            IPSecHelper::cleanup($this->identity->u32id);
            $this->api = new APIServer($this);
            $this->swu = new SWuClient("3566564213052700", $this->identity->getCardProvider(), $this->identity->u32id);
            $this->swu->run();
            $this->identity->ip_addr_client = $this->swu->addr_client;
            $this->identity->ip_addr_proxy = $this->swu->addr_pcscf;
            $this->identity->ip_proto_proxy = "tcp";
            Logger::info("P-CSCF Addr: {$this->identity->ip_addr_proxy}");
            $this->socket = new IMSSocketPool($this->identity->ip_addr_client);
            $this->socket->start($this->identity->ip_addr_proxy);
            $this->identity->security_client = new BasicSecurityHeaderParam(BasicSecurityHeaderParam::ALG_HMAC_MD5_96, BasicSecurityHeaderParam::EALG_NULL, $this->socket->client()->getPort(), $this->socket->server()->getPort(), mt_rand(0, pow(2, 32) - 1), mt_rand(0, pow(2, 32) - 1));
            $this->identity->setClientAddrInfo('tcp');
            (new RegisterSequence($this))->start();
            while (1) {
                try {
                    EventSocket::select(1);
                    $this->checkStatusPacket();
                } catch (DataParseException $p) {
                    Logger::alert("Data structe error: " . $p->getMessage());
                }
            }
        } catch (Throwable $t) {
            Logger::fail($t->getMessage() . "\n#! " . $t->getFile() . ":" . $t->getLine() . "\n" . $t->getTraceAsString());
            Logger::fail("System can't continue, exiting...");
        }
    }

    public function onReady()
    {
        $this->api->run();
    }

    public function onExit()
    {
        if ($this->shutdown) {
            return;
        }
        if ($this->api) {
            $this->api->shutdown();
        }
        if ($this->identity) {
            if ($this->identity->u32id !== null) {
                IPSecHelper::cleanup($this->identity->u32id);
            }
        }
        Logger::success("Bye");
        $this->shutdown = true;
        exit(0);
    }

    public function sendSMS($smsc, $target, $text)
    {
        $text_segs = str_split($text, 130);
        foreach ($text_segs as $seg) {
            Logger::info("Transmitting SMS to {$target} by SMSC {$smsc} with text: {$seg}");
            $tpdu = new SMSSubmit(new TPAddress($target), $this->rp_reference, $seg);
            $rpdu = new RPDataMSToNetwork($this->rp_reference, new RPAddress($smsc), $tpdu->pack());
            $pkt = new MessagePacket(new Transaction(), $this->identity, new TelUri($smsc));
            $pkt->appendBody($rpdu->pack());
            $this->waitStatusPacket($pkt->getHeader(CSeqHeader::getName()), [$this, 'handle_MESSAGE_ACK_REPLY']);
            $this->socket->client()->tcp()->send($pkt->toString());
            $this->rp_reference++;
        }
    }

    public function parsePacket($pkts)
    {
        try {
            foreach ($pkts as $pkt) {
                if ($pkt instanceof StatusPacket) {
                    $this->processStatusPacket($pkt);
                    continue;
                }
                if ($pkt instanceof RequestPacket) {
                    $requestline = $pkt->getHeader(RequestLine::getName());
                    switch ($requestline->method) {
                        case 'NOTIFY':
                            $this->processNotify($pkt);
                            break;
                        case 'MESSAGE':
                            $this->processMessage($pkt);
                            break;
                        default:
                            $status = new StatusPacket($pkt, 405);
                            $pkt->getIncomeSocket()->send($status->__toString());
                            throw new DataParseException("Unknown Request");
                    }
                }
            }
        } catch (DataParseException $e) {
            $dumpname = time() . mt_rand() . ".pktdump";
            Logger::warning("Packet processing error: " . $e->getMessage());
            file_put_contents($dumpname, serialize($pkt));
            Logger::warning("Packet dump stored in: {$dumpname}");
        }
    }

    private function processNotify(RequestPacket $pkt)
    {
        $status = new StatusPacket($pkt);
        Logger::debug("Received NOTIFY, simply ACK 200 OK and ignore...");
        $pkt->getIncomeSocket()->send($status->__toString());
    }

    private function processMessage(RequestPacket $pkt)
    {
        Logger::debug("Received MESSAGE");
        $status = new StatusPacket($pkt);
        Logger::debug("ACK with 200 OK...");
        $pkt->getIncomeSocket()->send($status->__toString());
        $callid = $pkt->getHeader(CallIDHeader::getName());
        $body = $pkt->getBody();

        $rpdu = GenericRP::unpack($body);
        if ($rpdu instanceof RPDataNetworkToMS) {
            $reply_uri = $pkt->getHeader(PAssertedIdentityHeader::getName());
            if ($reply_uri === null) {
                throw new DataParseException("P-Asserted-Identity not found");
            }
            $reply_pkt = new MessagePacket(new Transaction(), $this->identity, $reply_uri->uri);
            $reply_pkt->addHeader(new InReplyToHeader($callid));
            $ack = new RPAckMSToNetwork($rpdu);
            $reply_pkt->appendBody($ack->pack());
            $buf = $reply_pkt->toString();
            Logger::debug("ACK with GSM A-I/F RP...");
            $this->waitStatusPacket($reply_pkt->getHeader(CSeqHeader::getName()), [$this, 'handle_MESSAGE_ACK_REPLY']);
            $pkt->getIncomeSocket()->send($buf);

            $tpdu = SMSDeliver::unpack($rpdu->userdata);
            $sms_text = $tpdu->getUserDataUTF8();
            Logger::success("Incoming SMS with Call-ID: {$callid->value}, Originator: {$rpdu->originator->bcdnumber}, Sender: {$tpdu->originating->bcdnumber}, Encoding: {$tpdu->dcs->getEncoding()}, Data: {$sms_text}");
            $this->api->onSMS($rpdu->originator->bcdnumber, $tpdu->originating->bcdnumber, $sms_text);
        } else if ($rpdu instanceof RPAckNetworkToMS) {
            Logger::info("Incoming SMS ACK with Call-ID: {$callid->value}");
        }
    }

    private function handle_MESSAGE_ACK_REPLY(GenericPacket $pkt)
    {
        $status = $pkt->getHeader(StatusLine::getName());
        Logger::info("MESSAGE RP-ACK reply: {$status->code} {$status->reason}");
    }

    public function waitStatusPacket(CSeqHeader $cseq, callable $callback, $userdata = null, $timeout = 5)
    {
        $this->status_packet_handler[$cseq->seq] = ['callback' => $callback, 'timestamp' => time(), 'timeout' => $timeout, "userdata" => $userdata];
    }

    private function processStatusPacket(GenericPacket $pkt)
    {
        $cseq = $pkt->getHeader(CSeqHeader::getName());
        if ($cseq === null) {
            throw new DataParseException("CSeq not found");
        }
        if (isset($this->status_packet_handler[$cseq->seq])) {
            $handler = $this->status_packet_handler[$cseq->seq];
            unset($this->status_packet_handler[$cseq->seq]);
            $handler['callback']($pkt, $handler['userdata']);
        } else {
            throw new DataParseException("Income Unknown Status (CSeq: {$cseq->seq})");
        }
    }

    private function checkStatusPacket()
    {
        foreach ($this->status_packet_handler as $reg) {
            if ($reg['timeout']) {
                $diff = time() - $reg['timestamp'];
                if ($diff > $reg['timeout']) {
                    throw new FatalException("Reply timeout, maybe network error");
                }
            }
        }
    }

    public function getIdentity(): Identity
    {
        return $this->identity;
    }

    public function getSocketPool(): IMSSocketPool
    {
        return $this->socket;
    }

    public static function getInstance(): self
    {
        return self::$instance;
    }
}
