<?php

class Toucher
{
    # Methods
    public static function Touch($gid = "")
    {
        if (BELFRY2_ROLE == "GATE") (new Toucher())->DoGateTouch();
        elseif (BELFRY2_ROLE == "SERVER") (new Toucher())->DoServerTouch($gid);
    }
    public function DoGateTouch()
    {
        syslog(LOG_INFO, "gate -> cli -> touch branch");
        $data = array("id" => BELFRY2_GATE_ID, "url" => BELFRY2_GATE_URL);
        $com = new Command(Command::COM_TOUCH, $data, BELFRY2_SERVER_URL);
        $com->Send();
    }
    public function DoServerTouch($gid)
    {
        syslog(LOG_INFO, "server -> cli -> touch branch");
        $url = SDB::select()->from("gates")->where("id")->opEQ($gid)->exe();
        $url = $url[0]["url"];
        $data = array("id" => $gid);
        $com = new Command(Command::COM_TOUCH, $data, $url);
        $com->Send();
    }
    public static function ProcessTouch(array $data)
    {
        $id = $data["id"];
        $url = $data["url"];
        syslog(LOG_INFO, "synchronize with $id with $url");
        exec(BELFRY2_MAINDIR . "/cli/main.cli sync $id $url &");
    }
}
