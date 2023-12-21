<?php

namespace imsclient\protocol\gsm\tpdu;

use imsclient\datastruct\gsmcharset\Converter;
use imsclient\datastruct\gsmcharset\Packer;

class SMSSubmit
{
    /* InformationByte */
    public $rp;     // [7]
    public $udhi;   // [6]
    public $srr;    // [5]
    public $vpf;    // [3,4]
    public $rd;     // [2]
    public $mti;    // [0,1]
    /* END InformationByte */

    public $mr;     // u8

    /** @var TPAddress */
    public $destination;

    public $pid; // u8

    /** @var TPDCS */
    public $dcs;

    private $userdata_length; // u8
    private $userdata_header_length; // u8

    /** @var TPUserDataGenericHeader[] */
    public $userdata_header = [];

    public $userdata;

    public function __construct(TPAddress $destination, $message_reference, $data = "", $header = [])
    {
        $this->rp = 0; // TP Reply Path parameter is not set in this SMS SUBMIT/DELIVER
        $this->udhi = 0; // The TP UD field contains only the short message
        $this->srr = 0; // A status report is not requested
        $this->vpf = 0; // TP-VP field is not present
        $this->rd = 0; // Instruct SC to accept duplicates
        $this->mti = 1; // SMS-SUBMIT
        $this->pid = 0;
        $this->mr = $message_reference;
        $this->destination = $destination;
        $this->userdata_header = $header;
        $this->userdata = $data;
    }

    public function pack()
    {
        $bytes = "";
        try {
            $this->dcs = new TPDCS(TPDCS::GSM7BIT);
            $conv = new Converter();
            $conved = $conv->convertUtf8ToGsm($this->userdata, true);
            $packer = new Packer();
            $userdata_packed = $packer->pack($conved);
            $this->userdata_length = strlen($this->userdata);
        } catch (\InvalidArgumentException $i) {
            $this->dcs = new TPDCS(TPDCS::UCS2);
            $userdata_packed = mb_convert_encoding($this->userdata, 'UCS-2', 'UTF-8');
            $this->userdata_length = strlen($userdata_packed);
        }
        if (count($this->userdata_header) > 0) {
            $this->udhi = 1;
        }
        $bytes .= pack('C', $this->rp << 7 | $this->udhi << 6 | $this->srr << 5 | $this->vpf << 3 | $this->rd << 2 | $this->mti);
        $bytes .= pack('C', $this->mr);
        $bytes .= $this->destination->pack();
        $bytes .= pack('C', $this->pid);
        $bytes .= $this->dcs->pack();
        if ($this->udhi) {
            $bytes_udh = "";
            foreach ($this->userdata_header as $header) {
                $bytes_udh .= $header->pack();
            }
            $this->userdata_header_length = strlen($bytes_udh);
            $this->userdata_length += $this->userdata_header_length + 1;
        }
        $bytes .= pack('C', $this->userdata_length);
        if ($this->udhi) {
            $bytes .= pack('C', $this->userdata_header_length);
            $bytes .= $bytes_udh;
        }
        $bytes .= $userdata_packed;
        return $bytes;
    }
}
