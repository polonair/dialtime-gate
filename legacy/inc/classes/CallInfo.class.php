<?php

class CallInfo
{
    # Fields
    private $_Agi;
    private $_Caller;
    private $_IncomeDongle;

    # Properties
    public function getCaller()
    {
        return $this->_Caller;
    }
    public function getIncomeDongle()
    {
        return $this->_IncomeDongle;
    }
    public function getAgi()
    {
        return $this->_Agi;
    }

    # Methods
    public function __construct()
    {
        $this->_Agi = new Agi();
        $this->_Caller = new Caller($this->_Agi->request["agi_callerid"]);
        //$this->_IncomeDongle = new Dongle($this->_Agi->request["agi_calleridname"]);
        $this->_IncomeDongle = new Dongle($this->_Agi->request["agi_dnid"]);
    }
    public function Kill()
    {
        $this->_Agi->hangup();
    }
    public static function IsSpam($callInfo)
    {
        $row = GDB::select()->from("routes") #
            ->where("client_ph")->opEQ($callInfo->getCaller()->getPhone()) #
            ->opAND("state")->opEQ("spam")->exe();
        syslog(LOG_INFO, "is spam = " . count($row));
        return count($row);
    }
}
