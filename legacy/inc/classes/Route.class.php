<?php

class Route
{
    # Constants
    const USER2CLIENT = 1000;
    const CLIENT2USER = 1001;
    const UNKNOWN_DIR = 1002;

    # Fields
    private $_UserId;
    private $_UserDongle;
    private $_ClientPhone;
    private $_ClientDongle;
    private $_CallInfo;
    private $_Hash;
    private $_Created;
    private $_Expired;

    # Properties
    public function getAgi()
    {
        return $this->_CallInfo->getAgi();
    }
    public function getCallInfo()
    {
        return $this->_CallInfo;
    }
    public function getDialThrough()
    {
        return $this->_CallInfo->getCaller()->isUser() ? $this->_ClientDongle->getName() :
            $this->_UserDongle->getName();
    }
    public function getDialTo()
    {
        return $this->_CallInfo->getCaller()->isUser() ? $this->_ClientPhone : User::
            GetPhone($this->_UserId);
    }
    public function getHash()
    {
        return $this->_Hash;
    }
    public function getUserId()
    {
        return $this->_UserId;
    }

    # Methods
    public function __construct(CallInfo $callInfo, $userId, Dongle $userDongle, $clientPhone,
        Dongle $clientDongle)
    {
        $this->_Created = time();
        $this->_Expired = $this->_Created + 60 * 60 * 24 * 14;
        $this->_UserId = $userId;
        $this->_UserDongle = $userDongle;
        $this->_ClientPhone = $clientPhone;
        $this->_ClientDongle = $clientDongle;
        $this->_CallInfo = $callInfo;
        $this->_Hash = md5($this->_Created . $this->_Expired . $this->_UserId . $this->
            _UserDongle->getImsi() . $this->_ClientPhone . $this->_ClientDongle->getImsi());
    }
	public static function Exists($userId, $clientPhone)
	{
        $row = GDB::select()->from("routes") #
            ->where("client_ph")->opEQ($clientPhone) #
            ->opAND("user_id")->opEQ($userId)->exe();
        syslog(LOG_INFO, "route exists = " . count($row));
        return count($row);
	}
    public static function Load(CallInfo $callInfo)
    {
        $row = self::_LoadRow($callInfo);
        if (count($row) == 0) return null;
        $row = $row[0];
        $route = new Route($callInfo, $row["user_id"], new Dongle($row["user_dn"]), $row["client_ph"],
            new Dongle($row["client_dn"]));
        $route->_Hash = $row["hash"];
        $route->_Created = $row["created"];
        $route->_Expired = $row["expired"];
        return $route;
    }
    private static function _LoadRow(CallInfo $callInfo)
    {
        $caller = $callInfo->getCaller();
        $dongle = $callInfo->getIncomeDongle();
        if ($callInfo->getCaller()->isUser())
        {
            return GDB::select()->from("routes")->where("user_id")->opEQ($caller->getUserId
                ())->opAND("user_dn")->opEQ($dongle->getImsi())->opAND("state")->opEQ("active")->
                exe();
        }
        else
        {
            return GDB::select()->from("routes")->where("client_ph")->opEQ($caller->
                getPhone())->opAND("client_dn")->opEQ($dongle->getImsi())->opAND("state")->
                opNEQ("abused")->opAND("state")->
                opNEQ("forbidden")->opAND("state")->
                opNEQ("spam")->exe();
        }
    }
    public function Play()
    {
        return (new Call($this, false))->Run();
    }
    public function PlayFirst()
    {
        return (new Call($this, true))->Run();
    }
    public function Save()
    {
        GDB::insert()->into("routes")->values(array(
            "hash" => $this->_Hash,
            "user_id" => $this->_UserId,
            "user_dn" => $this->_UserDongle->getImsi(),
            "client_ph" => $this->_ClientPhone,
            "client_dn" => $this->_ClientDongle->getImsi(),
            "created" => $this->_Created,
            "state" => "active",
            "expired" => $this->_Expired))->exe();
    }
}
