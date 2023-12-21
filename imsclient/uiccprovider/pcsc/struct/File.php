<?php

namespace imsclient\uiccprovider\pcsc\struct;

abstract class File
{
    const MF_ID = "\x3F\x00";
    const EF_DIR_ID = "\x2F\x00";
    const EF_ICCID_PATH = "\x2F\xE2";
    const EF_IMSI_ID = "\x6F\x07";
    const EF_SMSP_ID = "\x6F\x42";
}
