<?php

class Recorder
{
    # Methods
    public static function UpdateRecords()
    {
        return (new Recorder())->Update();
    }
    public function Update()
    {
        syslog(LOG_INFO, "updating records");
        $records = SDB::select()->from("calls")->where("state")->opEQ("new")->exe();
        foreach ($records as $rec)
        {
            $hash = $rec["hash"];
            $path = BELFRY2_STOREDIR . "/records/call_$hash.mp3";
            SDB::update("calls")->set(array("state" => "requested"))->where("hash")->opEQ($hash)->
                exe();
            if (!file_exists($path) || (md5_file($path) != $rec["rec_hash"]))
            {
                $url = SDB::select()->from("gates")->where("id")->opEQ($rec["gate"])->exe();
                $url = $url[0]["url"];
                exec("wget -q -O $path http://$url/fapi/records/call_$hash.mp3 &");
            }
        }
    }
}
