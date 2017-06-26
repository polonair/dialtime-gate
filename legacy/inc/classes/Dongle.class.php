<?php

class Dongle
{
    # Fields
    private $_DongleName;
    private $_Imsi;

    # Properties
    public function getImsi()
    {
        return $this->_Imsi;
    }
    public function getName()
    {
        return $this->_DongleName;
    }

    # Methods
    public function __construct($dongleString)
    {
        /*$row = GDB::select("name", "imsi")->from("dongles")->where(Dongle::IsImsi($dongleString) ?
            "imsi" : "name")->opEQ($dongleString)->exe();
        $this->_Imsi = $row[0]["imsi"];
        $this->_DongleName = $row[0]["name"];*/
        $row = GDB::select("name", "imsi")->from("dongles")->where(Dongle::IsImsi($dongleString) ? "imsi" : "name")->opEQ($dongleString)->exe();
        $this->_Imsi = $row[0]["imsi"];
        $this->_DongleName = $row[0]["name"];
    }
    public static function IsImsi($string)
    {
        return (strlen($string) > 11);
    }
    /*public static function UpdateDongles()
    {
        $min = floor(time() / 60);
        $var = rand(1, BELFRY2_PINGPERIOD);
        if ($min % $var) return;
        $datas = array();
        exec(BELFRY2_PATH2_ASTERISK . " -x 'dongle show devices'", $datas);
        foreach ($datas as $data)
        {
            $d = preg_split("/( )+/", $data);
            if (count($d) == 12)
            {
                $dongle = GDB::select()->from("dongles")->where("name")->opEQ($d[0])->exe();
                if (count($dongle) > 0)
                {
                    GDB::update("dongles")->set(array(
                        "name" => $d[0],
                        "state" => $d[2],
                        "rssi" => $d[3],
                        "mode" => $d[4],
                        "submode" => $d[5],
                        "provider" => $d[6],
                        "model" => $d[7],
                        "firmware" => $d[8],
                        "imei" => $d[9],
                        "imsi" => $d[10],
                        "updated" => time()))->where("name")->opEQ($d[0])->exe();
                }
                else
                {
                    GDB::insert()->into("dongles")->values(array(
                        "name" => $d[0],
                        "state" => $d[2],
                        "rssi" => $d[3],
                        "mode" => $d[4],
                        "submode" => $d[5],
                        "provider" => $d[6],
                        "model" => $d[7],
                        "firmware" => $d[8],
                        "imei" => $d[9],
                        "imsi" => $d[10],
                        "updated" => time()))->exe();
                }
            }
        }
    }*/
    public static function UpdateDongles()
    {
        $min = floor(time() / 60);
        $var = rand(1, BELFRY2_PINGPERIOD);
        if ($min % $var) return;
        $datas = array();
        exec(BELFRY2_PATH2_ASTERISK . " -x 'sip show registry'", $datas);

        foreach ($datas as $data)
        {
            $d = preg_split("/( )+/", $data);
            if ($d[0] != "Host" && count($d) > 4 && preg_match("/[0-7]*@/", $d[2]))
            {
                $phone = str_replace("@", "", $d[2]);
                $dongle = GDB::select()->from("dongles")->where("name")->opEQ($phone)->exe();
                if (count($dongle) > 0)
                {
                    GDB::update("dongles")->set(array(
                        "name" => $phone,
                        "state" => $d[4],
                        "rssi" => $d[3],
                        "mode" => 3,
                        "submode" => 3,
                        "provider" => "MEGAFON",
                        "model" => "SIP",
                        "firmware" => "0.0.0.0",
                        "imei" => "349".$phone,
                        "imsi" => "259".$phone,
                        "updated" => time()))->where("name")->opEQ($phone)->exe();
                }
                else
                {
                    GDB::insert()->into("dongles")->values(array(
                        "name" => $phone,
                        "state" => $d[4],
                        "rssi" => $d[3],
                        "mode" => 3,
                        "submode" => 3,
                        "provider" => "MEGAFON",
                        "model" => "SIP",
                        "firmware" => "0.0.0.0",
                        "imei" => "349".$phone,
                        "imsi" => "259".$phone,
                        "updated" => time()))->exe();
                }
            }
        }
    }
	
}
