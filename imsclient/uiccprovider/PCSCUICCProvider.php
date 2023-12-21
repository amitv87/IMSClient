<?php

namespace imsclient\uiccprovider;

use imsclient\datastruct\StringStream;
use imsclient\exception\DataParseException;
use imsclient\exception\FatalException;
use imsclient\log\Logger;
use imsclient\uiccprovider\pcsc\apdu\APDUResponse;
use imsclient\uiccprovider\pcsc\apdu\SW;
use imsclient\uiccprovider\pcsc\apdu\GetResponseAPDU;
use imsclient\uiccprovider\pcsc\apdu\InternalAuthenticateAPDU;
use imsclient\uiccprovider\pcsc\apdu\ReadBinaryAPDU;
use imsclient\uiccprovider\pcsc\apdu\ReadRecordAPDU;
use imsclient\uiccprovider\pcsc\struct\EFDIRRecord;
use imsclient\uiccprovider\pcsc\struct\FCI;
use imsclient\uiccprovider\pcsc\struct\File;
use imsclient\uiccprovider\pcsc\pcsclite\Card;
use imsclient\uiccprovider\pcsc\pcsclite\PCSC;
use imsclient\uiccprovider\pcsc\pcsclite\Reader;
use imsclient\uiccprovider\pcsc\SelectHelper;
use imsclient\uiccprovider\UICCProvider;
use Throwable;

class PCSCUICCProvider implements UICCProvider
{
    /** @var PCSC */
    private $pcsc;
    /** @var Reader */
    private $reader;
    /** @var Card */
    private $card;

    private $aid;
    private $iccid;
    private $imsi;
    private $mcc;
    private $mnc;
    private $smsc;

    public function __construct()
    {
        $this->pcsc = new PCSC();
        $readers = $this->pcsc->getReaders();
        if (count($readers) == 0) {
            throw new FatalException("No smartcard reader!");
        }
        foreach ($readers as $reader) {
            Logger::info("Found reader: " . $reader->getName());
        }
        $this->reader = $readers[0];
        Logger::success("Use reader: " . $this->reader->getName());
        $this->card = $this->reader->getCard();
        if (!$this->card) {
            throw new FatalException("No smartcard inserted!");
        }

        $this->aid = "";
        $this->parse_EF_DIR();
        $this->parse_ICCID();
        $this->parse_IMSI();
        $this->smsc = "";
        try {
            $this->parse_SMSC();
        } catch (Throwable $t) {
            Logger::warning("Failed to parse SMSC: {$t->getMessage()}");
        }
    }

    private function parse_EF_DIR()
    {
        SelectHelper::id($this->card, File::MF_ID);
        $fci = SelectHelper::id($this->card, File::EF_DIR_ID);
        if ($fci->file_struct !== FCI::FILE_STRUCT_LINERFIXED) {
            throw new DataParseException("Uncommon EF_DIR_ID struct: {$fci->file_struct}");
        }
        for ($i = 1; $i <= $fci->file_record_count; $i++) {
            $apdu = new ReadRecordAPDU($i, $fci->file_record_size);
            $res = APDUResponse::unpack($this->card->transmit($apdu));
            $record = EFDIRRecord::unpack($res->data);
            if ($this->aid == "") {
                $this->aid = $record->aid;
            }
            if ($record->label == "USIM") {
                break;
            }
        }
        if ($this->aid === null) {
            throw new DataParseException("Failed to find USIM AID");
        }
    }

    private function select()
    {
        SelectHelper::name($this->card, $this->aid);
    }

    private function parse_ICCID()
    {
        $this->select();
        $fci = SelectHelper::path($this->card, File::EF_ICCID_PATH);
        if ($fci->file_struct != FCI::FILE_STRUCT_TRANSPARTENT) {
            throw new DataParseException("Uncommon EF_ICCID struct: {$fci->file_struct}");
        }
        $res = APDUResponse::unpack($this->card->transmit(new ReadBinaryAPDU($fci->file_size)));
        if ($res->sw1 !== SW::SW1_OK) {
            throw new DataParseException("Failed to read EF_ICCID");
        }
        $number = $res->data;
        $number = strtolower(bin2hex($number));
        $number = str_split($number, 2);
        array_walk($number, function (&$value) {
            $value = strrev($value);
        });
        $number = implode('', $number);
        $number = ltrim($number, '9');
        $number = rtrim($number, 'f');
        $this->iccid = $number;
    }

    private function parse_IMSI()
    {
        $this->select();
        $fci = SelectHelper::id($this->card, File::EF_IMSI_ID);
        if ($fci->file_struct != FCI::FILE_STRUCT_TRANSPARTENT) {
            throw new DataParseException("Uncommon EF_IMSI file struct: {$fci->file_struct}");
        }
        $res = APDUResponse::unpack($this->card->transmit(new ReadBinaryAPDU($fci->file_size)));
        if ($res->sw1 !== SW::SW1_OK) {
            throw new DataParseException("Failed to read EF_IMSI");
        }
        $stream = new StringStream($res->data);
        $number_len = $stream->readU8();
        $number = $stream->read($number_len);
        $number = strtolower(bin2hex($number));
        $number = str_split($number, 2);
        array_walk($number, function (&$value) {
            $value = strrev($value);
        });
        $number = implode('', $number);
        $number = ltrim($number, '9');
        $number = rtrim($number, 'f');
        $this->imsi = $number;
        $this->mcc = substr($this->imsi, 0, 3);
        $split = str_split($this->imsi);
        if ($split[3] == '0' || $split[3] == '1') {
            $this->mnc = substr($this->imsi, 3, 2);
        } else {
            $this->mnc = substr($this->imsi, 3, 3);
        }
        $this->mnc = str_pad($this->mnc, 3, '0', STR_PAD_LEFT);
    }

    private function parse_SMSC()
    {
        $this->select();
        $fci = SelectHelper::id($this->card, File::EF_SMSP_ID);
        if ($fci->file_struct != FCI::FILE_STRUCT_LINERFIXED) {
            throw new DataParseException("Uncommon EF_SMSP file struct");
        }
        $Y = $fci->file_record_size - 28;
        for ($i = 1; $i <= $fci->file_record_count; $i++) {
            $res = APDUResponse::unpack($this->card->transmit(new ReadRecordAPDU($i, $fci->file_record_size)));
            if ($res->sw1 !== SW::SW1_OK) {
                throw new DataParseException("Failed to read EF_SMSP");
            }
            $stream = new StringStream($res->data);
            $alpha_id = rtrim($stream->read($Y), "\xFF");
            $parameter_indicator = $stream->readU8();
            $tp_destination_address = $stream->read(12);
            $ts_service_centre_address = $stream->read(12);
            $stream = new StringStream($ts_service_centre_address);
            $length = $stream->readU8();
            $type_of_address = $stream->readU8();
            $number = $stream->read($length - 1);
            $number = strtolower(bin2hex($number));
            $number = str_split($number, 2);
            array_walk($number, function (&$value) {
                $value = strrev($value);
            });
            $number = implode('', $number);
            $number = rtrim($number, 'f');
            if ($number != "") {
                switch ($type_of_address) {
                    case 0x91:
                        $this->smsc = "+{$number}";
                        break;
                    default:
                        throw new DataParseException("Uncommon SMSC Address Type: {$type_of_address}");
                }
                break;
            }
        }
    }

    public function auth($rand, $autn)
    {
        if (strlen($rand) != 16 || strlen($autn) != 16) {
            throw new DataParseException("Uncommon 3G auth length");
        }
        $res = APDUResponse::unpack($this->card->transmit(new InternalAuthenticateAPDU(InternalAuthenticateAPDU::METHOD_3G, "\x10{$rand}\x10{$autn}")));
        if ($res->sw1 !== SW::SW1_LAST) {
            throw new DataParseException("Failed to Authenticate: " . var_dump($res));
        }
        $res = APDUResponse::unpack($this->card->transmit(new GetResponseAPDU($res->sw2)));
        if ($res->sw1 !== SW::SW1_OK) {
            throw new DataParseException("Failed to read Auth: " . var_dump($res));
        }
        $stream = new StringStream($res->data);
        $tag = $stream->readU8();
        if ($tag != 0xDB) {
            throw new DataParseException("Uncommon Authenticate result");
        }
        $length = $stream->readU8();
        $res = $stream->read($length);
        $length = $stream->readU8();
        $ck = $stream->read($length);
        $length = $stream->readU8();
        $ik = $stream->read($length);
        return ['res' => $res, 'ck' => $ck, 'ik' => $ik];
    }

    public function getICCID()
    {
        return $this->iccid;
    }

    public function getIMSI()
    {
        return $this->imsi;
    }

    public function getMCC()
    {
        return $this->mcc;
    }

    public function getMNC()
    {
        return $this->mnc;
    }

    public function getSMSC()
    {
        return $this->smsc;
    }

    public function getIMS()
    {
        return null;
    }

    public function __destruct()
    {
        $this->card->disconnect();
    }

    public function __debugInfo()
    {
        return [
            'aid' => bin2hex($this->aid),
            'iccid' => $this->iccid,
            'imsi' => $this->imsi,
            'mcc' => $this->mcc,
            'mnc' => $this->mnc,
            'smsc' => $this->smsc,
        ];
    }
}
