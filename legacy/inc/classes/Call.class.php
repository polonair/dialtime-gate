<?php

class Call
{
    # Fields
    private $_Route;
    private $_Time;
    private $_Result;
    private $_Direction;
    private $_Hash;

    # Properties
    public function getHash()
    {
        return $this->_Hash;
    }

    # Methods
    public function __construct(Route $route, $isRg)
    {
        $this->_Route = $route;
        $this->_Direction = $this->_Route->getCallInfo()->getCaller()->isUser() ? "U2C" :
            "C2U";
        if ($isRg) $this->_Direction = "RG";
        $this->_Time = time();
        $this->_Hash = md5($this->_Time . md5($this->_Time + 1) . $this->_Direction . $this->
            _Route->getHash());
    }
    public function Run()
    {
        $agi = $this->_Route->getAgi();
        $dial_to = $this->_Route->getDialTo();
        $dial_through = $this->_Route->getDialThrough();
        $this->_StartRecord($agi);
        $this->_Dial($agi, $dial_through, $dial_to);
        $this->_Save();
        return $this->_Result;
    }
    private function _Save()
    {
        $wav = BELFRY2_STOREDIR . "/records/call_" . $this->getHash() . ".wav";
        $mp3 = BELFRY2_STOREDIR . "/records/call_" . $this->getHash() . ".mp3";
        exec("chmod -c 0777 $wav");
        if (file_exists($wav))
        {
            exec("lame -V 7 $wav $mp3");
            exec("rm -rf $wav");
        }
        else
        {
            exec("echo no-record >> $mp3");
        }
        exec("chmod -c 0777 $mp3");
        $mp3hash = md5_file($mp3);
		if ($this->_Direction == 'RG' && !$this->_Result->statusAnswered())
		{
			GDB::insert()->into("calls")->values(array(
				"hash" => $this->getHash(),
				"route" => $this->_Route->getHash(),
				"direction" => $this->_Direction,
				"result" => $this->_Result->getStatus(),
				"dial_length" => $this->_Route->getUserId(),
				"answ_length" => $this->_Result->getAnswerTime(),
				"created_on" => $this->_Time,
				"rec_hash" => $mp3hash))->exe();
		}
		else
		{
			GDB::insert()->into("calls")->values(array(
				"hash" => $this->getHash(),
				"route" => $this->_Route->getHash(),
				"direction" => $this->_Direction,
				"result" => $this->_Result->getStatus(),
				"dial_length" => $this->_Result->getDialLength(),
				"answ_length" => $this->_Result->getAnswerTime(),
				"created_on" => $this->_Time,
				"rec_hash" => $mp3hash))->exe();
		}
    }
    private function _StartRecord($agi)
    {
        $filename = BELFRY2_STOREDIR . "/records/call_" . $this->getHash() . ".wav";
        $agi->exec('MixMonitor', $filename, "b");
    }
    private function _Dial($agi, $dial_through, $dial_to)
    {
        /*$agi->exec_dial("Dongle/" . $dial_through, "holdother:" . $dial_to,
            BELFRY2_CALLTIME);*/
			
		$agi->exec_dial("SIP/" . $dial_through, str_replace("+", "", $dial_to));

        $dialed_time = $agi->get_variable("DIALEDTIME", true);
        $answered_time = $agi->get_variable("ANSWEREDTIME", true);
        $dial_status = $agi->get_variable("DIALSTATUS", true);

        $this->_Result = new DialResult($dial_status, $dialed_time, $answered_time);
    }
}
