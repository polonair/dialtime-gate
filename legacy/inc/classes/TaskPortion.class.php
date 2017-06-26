<?php

class TaskPortion
{
    # Fields
    private $_UserId;
    private $_GateId;
    private $_Dongles;

    # Properties
    public function getGateId()
    {
        return $this->_GateId;
    }
    public function getUserId()
    {
        return $this->_UserId;
    }
    public function getDongles()
    {
        return json_encode($this->_Dongles);
    }

    # Methods
    public function __construct($userId, $gateId)
    {
        $this->_UserId = $userId;
        $this->_GateId = $gateId;
        $dongles = SDB::select("id")->from("dongles")->where("gate_id")->opEQ($this->
            _GateId)->exe();
        shuffle($dongles);
        $this->_Dongles = array();
        foreach ($dongles as $dongle) $this->_Dongles[] = $dongle["id"];
    }
    public function Clean()
    {
        $dongles = $this->_Dongles;
        $new_dongles = array();
        foreach ($dongles as $dongle)
        {
            if (SDB::select()->from("routes")->where("user_id")->opEQ($this->_UserId)->
                opAND("user_dn")->opEQ($dongle)->count() === 0) $new_dongles[] = $dongle;
        }
        $this->_Dongles = $new_dongles;
    }
    public function Save()
    {
        SDB::insert()->into("tasks")->values(array(
            "user_id" => $this->_UserId,
            "dongles" => json_encode($this->_Dongles),
            "expires_on" => time() + 60 * 3,
            "gate_id" => $this->_GateId,
            "state" => "active"))->exe();
    }
}
