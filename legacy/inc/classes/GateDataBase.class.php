<?php

class GateDataBase extends SQLite3 implements DataBase
{
    # Constants
    const DB_FILENAME = "/var/lib/belfry2/belfry.db";
    const DB_INIT_SQL = <<< SQL
CREATE TABLE IF NOT EXISTS `dongles` (
	`name`	TEXT,
	`state`	TEXT,
	`rssi`	INTEGER,
	`mode`	INTEGER,
	`submode`	INTEGER,
	`provider`	TEXT,
	`model`	TEXT,
	`firmware`	TEXT,
	`imei`	TEXT,
	`imsi`	TEXT,
	`updated`	INTEGER
);
CREATE TABLE IF NOT EXISTS `routes` (
    `route_hash` TEXT,
	`user_id`	 INTEGER,
	`user_dn`	 TEXT,
	`client_ph`	 TEXT,
	`client_dn`	 TEXT
);
CREATE TABLE IF NOT EXISTS `tasks` (
	`task_id`	INTEGER PRIMARY KEY AUTOINCREMENT,
	`user_id`	INTEGER,
	`dongles`	TEXT,
	`expires_on`	INTEGER
);
CREATE TABLE IF NOT EXISTS `users` (
	`id`	INTEGER,
	`phone`	TEXT,
	`service`	TEXT,
	PRIMARY KEY(id)
);
SQL;
    # Methods
    public function __construct()
    {
        if (!file_exists(GateDataBase::DB_FILENAME))
        {
            parent::__construct(GateDataBase::DB_FILENAME, SQLITE3_OPEN_READWRITE |
                SQLITE3_OPEN_CREATE);
            chmod(GateDataBase::DB_FILENAME, 0666);
        }
        else
        {
            parent::__construct(GateDataBase::DB_FILENAME, SQLITE3_OPEN_READWRITE |
                SQLITE3_OPEN_CREATE);
        }
        $this->_CheckTables();
        $this->busyTimeout(30000);
    }
    private function _CheckTables()
    {
        $this->exec(GateDataBase::DB_INIT_SQL);
    }
    public function IsUserPhone($callerPhone)
    {
        $statement = $this->prepare("SELECT * FROM `users` WHERE `phone` = :phone; ");
        $statement->bindValue(":phone", $callerPhone);
        $result = $statement->execute();
        if ($row = $result->fetchArray(SQLITE3_ASSOC)) return true;
        return false;
    }
    public function IsServiceRoute($callerPhone, $incomeDongle)
    {
        $statement = $this->prepare("SELECT * FROM `users` WHERE `phone` = :phone AND `service` = :dongle; ");
        $statement->bindValue(":phone", $callerPhone);
        $statement->bindValue(":dongle", $incomeDongle);
        $result = $statement->execute();
        if ($row = $result->fetchArray(SQLITE3_ASSOC)) return true;
        return false;
    }
    public function IsUserRouteEnd($callerPhone, $incomeDongle)
    {
        if (($userId = $this->_GetUserId($callerPhone)) > 0)
        {
            $statement = $this->prepare("SELECT * FROM `routes` WHERE `user_id` = :uid AND `user_dn` = :udn; ");
            $statement->bindValue(":uid", $userId);
            $statement->bindValue(":udn", $incomeDongle);
            $result = $statement->execute();
            if ($row = $result->fetchArray(SQLITE3_ASSOC)) return true;
            return false;
        }
        return false;
    }
    public function IsClientRouteEnd($callerPhone, $incomeDongle)
    {
        $statement = $this->prepare("SELECT * FROM `routes` WHERE `client_ph` = :cph AND `client_dn` = :cdn; ");
        $statement->bindValue(":cph", $callerPhone);
        $statement->bindValue(":cdn", $incomeDongle);
        $result = $statement->execute();
        if ($row = $result->fetchArray(SQLITE3_ASSOC)) return true;
        return false;
    }
    public function LoadRouteByUser($callerPhone, $incomeDongle)
    {
        if (($userId = $this->_GetUserId($callerPhone)) > 0)
        {
            $statement = $this->prepare("SELECT * FROM `routes` WHERE `user_id` = :uid AND `user_dn` = :udn; ");
            $statement->bindValue(":uid", $userId);
            $statement->bindValue(":udn", $incomeDongle);
            $result = $statement->execute();
            if ($row = $result->fetchArray(SQLITE3_ASSOC))
            {
                return new Route($row['user_id'], $row['user_dn'], $row['client_dn'], $row['client_ph'],
                    Route::USER2CLIENT, $this);
            }
            return false;
        }
        return false;
    }
    public function getDongleInnerName($dial_through)
    {
        //echo "getDongleInnerName($dial_through)";
        $statement = $this->prepare("SELECT * FROM `dongles` WHERE `imsi` = :imsi; ");
        $statement->bindValue(":imsi", $dial_through);
        $result = $statement->execute();
        if ($row = $result->fetchArray(SQLITE3_ASSOC)) return $row['name'];
        return (-1);
    }
    public function getDongleOuterName($dial)
    {
        $statement = $this->prepare("SELECT * FROM `dongles` WHERE `name` = :name; ");
        $statement->bindValue(":name", $dial);
        $result = $statement->execute();
        if ($row = $result->fetchArray(SQLITE3_ASSOC)) return $row['imsi'];
        return (-1);
    }
    public function GetUserPhone($userId)
    {
        $statement = $this->prepare("SELECT * FROM `users` WHERE `id` = :id; ");
        $statement->bindValue(":id", $userId);
        $result = $statement->execute();
        if ($row = $result->fetchArray(SQLITE3_ASSOC)) return $row['phone'];
        return (-1);
    }
    public function LoadRouteByClient($callerPhone, $incomeDongle)
    {
        $statement = $this->prepare("SELECT * FROM `routes` WHERE `client_ph` = :cph AND `client_dn` = :cdn; ");
        $statement->bindValue(":cph", $callerPhone);
        $statement->bindValue(":cdn", $incomeDongle);
        $result = $statement->execute();
        if ($row = $result->fetchArray(SQLITE3_ASSOC))
        {
            return new Route($row['user_id'], $row['user_dn'], $row['client_dn'], $row['client_ph'],
                Route::CLIENT2USER, $this);
        }
        return false;
    }
    public function LoadTask($callerPhone, $incomeDongle)
    {
        while (1 == 1)
        {
            $statement = $this->prepare("SELECT * FROM `tasks` LIMIT 1; ");
            $result = $statement->execute();
            if ($row = $result->fetchArray(SQLITE3_ASSOC))
            {
                $statement = $this->prepare("DELETE FROM `tasks` WHERE `task_id` = :tid;");
                $statement->bindValue(":tid", $row['task_id']);
                $statement->execute();
                if ($row['expires_on'] > time())
                {
                    return new Task($row['user_id'], $row['dongles']);
                }
            }
            else
            {
                break;
            }
        }
        return false;
    }
    public function SaveRoute($route)
    {
        $statement = $this->prepare("INSERT INTO `routes` (`user_id`, `user_dn`, `client_ph`, `client_dn`) VALUES (:uid, :udn, :cph, :cdn);");
        $statement->bindValue(":cph", $route->getClientPhone());
        $statement->bindValue(":cdn", $route->getClientDongle());
        $statement->bindValue(":uid", $route->getUserId());
        $statement->bindValue(":udn", $route->getUserDongle());
        $statement->execute();
    }
    public function LoadUsers()
    {
        $statement = $this->prepare("SELECT * FROM `users`;");
        $result = $statement->execute();
        $return = array();
        while ($row = $result->fetchArray(SQLITE3_ASSOC))
        {
            $return[] = $row;
        }
        return $return;
    }
    public function LoadRoutes()
    {
        $statement = $this->prepare("SELECT * FROM `routes`;");
        $result = $statement->execute();
        $return = array();
        while ($row = $result->fetchArray(SQLITE3_ASSOC))
        {
            $return[] = $row;
        }
        return $return;
    }
    public function SetUsers($data)
    {
        $statement = $this->prepare("DELETE FROM `users` WHERE 1 ;");
        $statement->execute();
        $statement->close();
        foreach ($data as $user)
        {
            $statement = $this->prepare("INSERT INTO `users` (`id`, `phone`, `service`) VALUES (:uid, :uph, 'no');");
            $statement->bindValue(":uid", $user["id"]);
            $statement->bindValue(":uph", $user["phone"]);
            $statement->execute();
            $statement->close();
        }
    }
    public function LoadDongles()
    {
        $statement = $this->prepare("SELECT `imsi` FROM `dongles`;");
        $result = $statement->execute();
        $return = array();
        while ($row = $result->fetchArray(SQLITE3_ASSOC))
        {
            $return[] = $row;
        }
        return $return;
    }
    public function UpdateDongles($name, $state, $rssi, $mode, $submode, $provider,
        $model, $firmware, $imei, $imsi)
    {
        $statement = $this->prepare("SELECT * FROM `dongles` WHERE `name` = :name; ");
        $statement->bindValue(":name", $name);
        $result = $statement->execute();
        $sql = "";
        if ($result->fetchArray())
        {
            $sql = <<< SQL
UPDATE 
    `dongles` 
SET 
    `name` = :name, 
    `state` = :state, 
    `rssi` = :rssi, 
    `mode` = :mode, 
    `submode` = :submode, 
    `provider` = :provider, 
    `model` = :model, 
    `firmware` = :firmware, 
    `imei` = :imei, 
    `imsi` = :imsi, 
    `updated` = :updated 
WHERE 
    `name` = :name            
SQL;
        }
        else
        {
            $sql = <<< SQL
INSERT INTO `dongles` (
    `name`, 
    `state`, 
    `rssi`, 
    `mode`, 
    `submode`, 
    `provider`, 
    `model`, 
    `firmware`, 
    `imei`, 
    `imsi`, 
    `updated`
) VALUES (
    :name, 
    :state, 
    :rssi, 
    :mode, 
    :submode, 
    :provider, 
    :model, 
    :firmware, 
    :imei, 
    :imsi, 
    :updated);          
SQL;
        }
        $statement = $this->prepare($sql);
        $statement->bindValue(":name", $name);
        $statement->bindValue(":state", $state);
        $statement->bindValue(":rssi", $rssi);
        $statement->bindValue(":mode", $mode);
        $statement->bindValue(":submode", $submode);
        $statement->bindValue(":provider", $provider);
        $statement->bindValue(":model", $model);
        $statement->bindValue(":firmware", $firmware);
        $statement->bindValue(":imei", $imei);
        $statement->bindValue(":imsi", $imsi);
        $statement->bindValue(":updated", time());
        $statement->execute();
    }
    public function UpdateTasks($data)
    {
        echo "updating tasks\r\n";
        echo "have data = ";
        var_dump($data);
        echo "deleting all tasks\r\n";
        $this->_ClearTasks();
        echo "done\r\n";
        echo "inserting all tasks\r\n";
        $sql = "INSERT INTO `tasks` (`user_id`, `dongles`, `expires_on`) VALUES (:uid, :dongles, :exp);";
        foreach ($data as $task)
        {
            $statement = $this->prepare($sql);
            $statement->bindValue(":uid", $task['user']);
            $statement->bindValue(":dongles", json_encode($task['dongles']));
            $statement->bindValue(":exp", time() + 60 * 3);
            $statement->execute();
            $statement->close();
        }
        echo "done\r\n";
    }

    private function _ClearTasks()
    {
        $statement = $this->prepare("DELETE FROM `tasks` WHERE 1 ;");
        $statement->execute();
        $statement->close();
    }
    private function _GetUserId($phone)
    {
        $statement = $this->prepare("SELECT * FROM `users` WHERE `phone` = :phone; ");
        $statement->bindValue(":phone", $phone);
        $result = $statement->execute();
        if ($row = $result->fetchArray(SQLITE3_ASSOC)) return $row['id'];
        return (-1);
    }
}
