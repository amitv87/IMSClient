<?php

namespace imsclient\uiccprovider\pcsc\struct;

abstract class Tag
{
    const FCI = 0x62;
    const FILE_LENGTH = 0x80;
    const FILE_DESCRIPTOR = 0x82;
    const RECORD = 0x61;
    const RECORD_AID = 0x4F;
    const RECORD_LABEL = 0x50;
}
