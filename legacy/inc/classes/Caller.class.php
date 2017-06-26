<?php

class Caller
{
    # Fields
    private $_Phone;
    private $_UserId;

    # Properties
    public function getUserId()
    {
        return $this->_UserId;
    }
    public function getPhone()
    {
        return $this->_Phone;
    }

    # Methods
    public function __construct($phone)
    {
        $this->_Phone = $phone;
        $row = GDB::select()->from("users")->where("phone")->opEQ("+".$this->_Phone)->exe();
        if (count($row) > 0) $this->_UserId = $row[0]["id"];
        else  $this->_UserId = 0;
    }
    public function isUser()
    {
        return ($this->_UserId > 0);
    }
}
