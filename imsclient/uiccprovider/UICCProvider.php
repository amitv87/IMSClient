<?php

namespace imsclient\uiccprovider;

interface UICCProvider
{
    public function auth($rand, $autn);
    public function getICCID();
    public function getIMSI();
    public function getMCC();
    public function getMNC();
    public function getSMSC();
    public function getIMS();
}
