<?php

class Patcher
{
    # Methods
    public static function Patch()
    {
        (new Patcher())->PatchAll();
    }
    public function PatchAll()
    {
        syslog(LOG_INFO, "patching gates");
        $gates = SDB::select()->from("gates")->exe();
        foreach ($gates as $gate) $this->_PatchGate($gate["id"]);
    }
    private function _PatchGate($gid)
    {
        $patch = $this->_CreatePatch($gid);
        if ($patch)
        {
            $this->_SavePatch($patch, $gid);
            exec(BELFRY2_MAINDIR . "/cli/main.cli touch $gid &");
            syslog(LOG_INFO, "PATCHING STARTED");
        }
        else
        {
            syslog(LOG_INFO, "NOTHING TO PATCH");
        }
    }
    private function _CreatePatch($gid)
    {
        $dbp = $this->_CreateDbPatch($gid);
        $fp = $this->_CreateFilePatch($gid);
        if (count($fp) > 0 || count($dbp) > 0) return array("db_patch" => $dbp,
                    "file_patch" => $fp);
        return false;
    }
    private function _CreateDbPatch($gid)
    {
        $path = BELFRY2_STOREDIR . "/localmirror/db_$gid";
        if (file_exists($path) && filesize($path) > 0)
        {
            $db = new SQLite3($path);
            $db->busyTimeout(30000);

            $patch = array();
            $patch = $this->__PatchCalls($db, $patch);
            $patch = $this->__PatchRoutes($db, $patch, $gid);
            $patch = $this->__PatchTasks($db, $patch, $gid);
            $patch = $this->__PatchUsers($db, $patch);
            return $patch;
        }
        return array();
    }
    private function __PatchCalls($db, $patch)
    {
        $result = $db->query("SELECT * FROM `calls` WHERE 1;");
        while ($row = $result->fetchArray(SQLITE3_ASSOC))
        {
            $hash = $row["hash"];
            if (SDB::select()->from("calls")->where("hash")->opEQ($hash)->count() > 0)
            {
                $patch[] = "DELETE FROM `calls` WHERE `hash` = '$hash'; ";
            }
        }
        return $patch;
    }
    private function __PatchRoutes($db, $patch, $gid)
    {
        $rts = SDB::select()->from("routes")->where("gate_id")->opEQ($gid)->exe();
        foreach ($rts as $route)
        {
            $hash = $route["hash"];
            $user_id = $route["user_id"];
            $user_dn = $route["user_dongle"];
            $client_ph = $route["client_phone"];
            $client_dn = $route["client_dongle"];
            $created = $route["created"];
            $expired = $route["expired"];
            $state = $route["state"];

            $result = $db->query("SELECT * FROM `routes` WHERE `hash` = '$hash';");
            if ($row = $result->fetchArray(SQLITE3_ASSOC))
            {
                if ($row["state"] != $state || $row["expired"] != $expired)
                {
                    $patch[] = "UPDATE `routes` SET `state` = '$state', `expired` = '$expired' WHERE `hash` = '$hash'; ";
                }
            }
            else
            {
                $patch[] = "INSERT INTO `routes`" .
                    "(`hash`, `user_id`, `user_dn`, `client_ph`, `client_dn`, `created`, `expired`) VALUES " .
                    "('$hash', '$user_id', '$user_dn', '$client_ph', '$client_dn', '$created', '$expired'); ";
            }
        }
        return $patch;
    }
    private function __PatchTasks($db, $patch, $gid)
    {
        $tsks = SDB::select()->from("tasks")->where("gate_id")->opEQ($gid)->opAND("state")->
            opEQ("active")->exe();
        $cnt = count($tsks);
        $jtasks = json_encode($tsks);
        syslog(LOG_INFO, "patching tasks: found $cnt tasks >> $jtasks");
        if (count($tsks) > 0)
        {
            $patch[] = "DELETE FROM `tasks` WHERE 1; ";
        }
        else
        {
            $result = $db->query("SELECT * FROM `tasks` WHERE 1;");
            if ($result->fetchArray(SQLITE3_ASSOC))
            {
                $patch[] = "DELETE FROM `tasks` WHERE 1; ";
            }
        }
        foreach ($tsks as $task)
        {
            $id = $task["id"];
            $uid = $task["user_id"];
            $cdongle = $task["client_dongle"];
            $dongles = $task["dongles"];
            $expires = $task["expires_on"];
            $patch[] = "INSERT INTO `tasks` (`id`, `user_id`, `income_dongle`, `dongles`, `expires_on`) VALUES ('$id', '$uid', '$cdongle', '$dongles', '$expires'); ";
        }
        return $patch;
    }
    private function __PatchUsers($db, $patch)
    {
        $usr = SDB::select("users.id", "users.phone")->from("users")->inner_join("clients")->
            on(array("users.id" => "clients.id"))->where("users.id")->opGT('1000')->exe();
        foreach ($usr as $user)
        {
            $id = $user["id"];
            $phone = $user["phone"];
            $result = $db->query("SELECT * FROM `users` WHERE `id` = '$id'; ");
            if ($row = $result->fetchArray(SQLITE3_ASSOC))
            {
                if ($row["phone"] != $phone)
                {
                    $patch[] = "UPDATE `users` SET `phone` = '$phone' WHERE `id` = '$id'; ";
                }
            }
            else
            {
                $patch[] = "INSERT INTO `users` (`id`, `phone`) VALUES ('$id', '$phone'); ";
            }
        }
        return $patch;
    }
    private function _CreateFilePatch($gid)
    {
        $patch = array();
        $records = SDB::select()->from("calls")->where("state")->opEQ("requested")->
            opAND("gate")->opEQ($gid)->exe();
        foreach ($records as $rec)
        {
            $hash = $rec["hash"];
            $path = BELFRY2_STOREDIR . "/records/call_$hash.mp3";
            if (file_exists($path) && (md5_file($path) == $rec["rec_hash"]))
            {
                SDB::update("calls")->set(array("state" => "store"))->where("hash")->opEQ($hash)->
                    exe();
                $patch[] = $hash;
            }
            else
            {
                SDB::update("calls")->set(array("state" => "new"))->where("hash")->opEQ($hash)->
                    exe();
            }
        }
        return $patch;
    }
    private function _SavePatch($patch, $gid)
    {
        $data = base64_encode(json_encode($patch));
        syslog(LOG_INFO, "patch for gate #$gid was saved with data [$data]");
        file_put_contents(BELFRY2_STOREDIR . "/patches/patch_$gid", $data);
    }
}
