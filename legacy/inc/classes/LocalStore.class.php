<?php

class LocalStore
{
    # Methods
    public static function Update($id, $url)
    {
        $path = BELFRY2_STOREDIR . "/localmirror";
        syslog(LOG_INFO, "synchronize with $id to $path/db_$id");
        exec("wget -q -O $path/db_$id http://$url/fapi/belfry.db");
        if (file_exists("$path/db_$id") && filesize("$path/db_$id") > 0)
        {
            syslog(LOG_INFO, "file downloaded");
            syslog(LOG_INFO, "loading and merging data");
            $db = new SQLite3("$path/db_$id");
            $db->busyTimeout(30000);

            $result = $db->query("SELECT * FROM `routes` WHERE 1");
            while ($row = $result->fetchArray(SQLITE3_ASSOC)) #
                     SDB::funcc("merge_route", $row["hash"], $row["user_id"], $row["user_dn"], $row["client_dn"],
                    $row["client_ph"], $id, $row["created"], $row["expired"], $row["state"])->exe();

            $result = $db->query("SELECT * FROM `calls` WHERE 1");
            while ($row = $result->fetchArray(SQLITE3_ASSOC)) #
                     SDB::funcc("merge_call", $row["hash"], $row["route"], $id, $row["direction"], $row["result"],
                    $row["dial_length"], $row["answ_length"], $row["created_on"], $row["rec_hash"])->
                    exe();

            $result = $db->query("SELECT * FROM `dongles` WHERE 1");
            while ($row = $result->fetchArray(SQLITE3_ASSOC)) #
                     SDB::funcc("merge_dongle", $row["imsi"], $id, $row["updated"])->exe();
        }
        else  syslog(LOG_INFO, "file not downloaded");
    }
}
