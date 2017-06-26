<?php

class Ping
{
    # Methods
    public static function SendPing()
    {
        $min = floor(time() / 60);
        $var = rand(1, BELFRY2_PINGPERIOD);
        if ($min % $var) return;
        $data = array(
            "id" => BELFRY2_GATE_ID,
            "url" => BELFRY2_GATE_URL,
            "sw" => BELFRY2_VERSION);
        $com = new Command(Command::COM_PING, $data, BELFRY2_SERVER_URL);
        $com->Send();
    }
    public static function ProcessPing($data)
    {
        $id = $data["id"];
        $url = $data["url"];
        $sw = $data["sw"];
        syslog(LOG_INFO, "processing ping from $id/$sw with $url ");
        $updated = time();
        if (SDB::select()->from("gates")->where("id")->opEQ($id)->count() > 0)
        {
            syslog(LOG_INFO, "update data");
            SDB::update("gates")->set(array(
                "url" => $url,
                "sw" => $sw,
                "updated" => $updated))->where("id")->opEQ($id)->exe();
        }
        else
        {
            syslog(LOG_INFO, "insert data");
            SDB::insert()->into("gates")->values(array(
                "id" => $id,
                "url" => $url,
                "sw" => $sw,
                "updated" => $updated))->exe();
            Toucher::ProcessTouch($data);
        }
        if (!file_exists(BELFRY2_MAINDIR . "/inner-web/sync/$id"))
        {
            system("ln -s " . BELFRY2_STOREDIR . "/patches/patch_$id " . BELFRY2_MAINDIR .
                "/inner-web/sync/$id");
        }
    }
}
