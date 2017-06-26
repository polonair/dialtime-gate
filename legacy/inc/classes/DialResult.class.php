<?php

class DialResult
{
    # Fields
    private $_DialStatus;
    private $_DialedTime;
    private $_AnsweredTime;

    # Properties
    public function getDialLength()
    {
        return $this->_DialedTime;
    }
    public function getAnswerTime()
    {
        return $this->_AnsweredTime;
    }
    public function getStatus()
    {
        return $this->_DialStatus;
    }

    # Methods
    public function __construct($dial_status, $dialed_time, $answered_time)
    {
        $this->_AnsweredTime = $answered_time;
        $this->_DialStatus = $dial_status;
        $this->_DialedTime = $dialed_time;
    }
    public function statusAnswered()
    {
        return ($this->_DialStatus === "ANSWER");
    }
    public function statusDeclined()
    {
        return ($this->_DialStatus === "NOANSWER") || ($this->_DialStatus === "BUSY") || ($this->
            _DialStatus === "CANCEL") || ($this->_DialStatus === "CONGESTION");
    }
    public function statusBroke()
    {
        return ($this->_DialStatus === "CHANUNAVAIL");
    }
}
