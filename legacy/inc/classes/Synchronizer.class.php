<?php

class Synchronizer
{
    # Methods
    public static function Synchronize($id = "", $url = "")
    {
        if (BELFRY2_ROLE == "GATE") (new Synchronizer())->DoGateSync();
        elseif (BELFRY2_ROLE == "SERVER") (new Synchronizer())->DoServerSync($id, $url);
    }
    public function DoGateSync()
    {
        syslog(LOG_INFO, "gate -> cli -> sync branch");
        $dbPatch = array();
        $files2Clean = array();
        $path = BELFRY2_STOREDIR . "/sync-data";
        syslog(LOG_INFO, "start downloading patch from " . BELFRY2_SERVER_URL);
        exec("wget -q -O $path http://" . BELFRY2_SERVER_URL . "/sync/" .
            BELFRY2_GATE_ID);
        syslog(LOG_DEBUG, "patch location: " . realpath($path));
        if (file_exists($path) && filesize($path) > 0)
        {
            syslog(LOG_INFO, "patch downloaded, start patching");
            $raw_data = file_get_contents($path);
            syslog(LOG_DEBUG, "patch data: [$raw_data]");
            $dec = json_decode(base64_decode($raw_data), true);
            $dbPatch = $dec["db_patch"];
            $files2Clean = $dec["file_patch"];
            if (count($dbPatch) > 0)
            {
                foreach ($dbPatch as $q) GDB::exe_raw($q);
            }
            if (count($files2Clean) > 0)
            {
                foreach ($files2Clean as $file)
                {
                    $path = BELFRY2_STOREDIR . "/records/call_$file.mp3";
                    exec("rm $path");
                }
            }
            syslog(LOG_DEBUG, "patch done");
        }
        exec("rm $path");
    }
    public function DoServerSync($id, $url)
    {
        syslog(LOG_INFO, "server -> cli -> sync branch");
        LocalStore::Update($id, $url);
    }
}
