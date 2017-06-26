<?php

class ServerDataBase extends mysqli implements DataBase
{
    public function __construct()
    {
        parent::__construct("localhost", "root", "persio", "msdb");
        $this->set_charset("utf8");
    }
    public function LoadUsers()
    {
        $result = $this->query("SELECT * FROM `users`;");
        $return = array();
        while ($row = $result->fetch_assoc())
        {
            $return[] = $row;
        }
        return $return;
    }
    public function LoadRoutes()
    {
        $result = $this->query("SELECT * FROM `routes`;");
        $return = array();
        while ($row = $result->fetch_assoc())
        {
            $return[] = array(
                "user_id" => $row["user_id"],
                "user_dn" => $row["user_dongle"],
                "client_dn" => $row["client_dongle"],
                "client_ph" => $row["client_phone"],
                );
        }
        return $return;
    }
    public function GateExists($settings)
    {
        $id = $settings['id'];
        $result = $this->query("SELECT * FROM `gates` WHERE `id` = '$id';");
        $return = array();
        if ($row = $result->fetch_assoc()) return true;
        return false;
    }
    public function GetGateUrl($gid)
    {
        $result = $this->query("SELECT * FROM `gates` WHERE `id` = '$gid';");
        if ($row = $result->fetch_assoc()) return $row['url'];
        return false;
    }
    public function SetRoutes($routes, $gid)
    {
    }
    public function SetDongles($dongles, $gid)
    {
        foreach ($dongles as $dongle)
        {
            $this->query("INSERT IGNORE INTO `dongles` (`id`, `gate_id`) VALUES ('" . $dongle["imsi"] .
                "', '$gid')");
        }
    }
    public function CheckPrivateCredentials($credentials)
    {
        $phone = $credentials["phone"];
        $password = $credentials["pass"];
        $result = $this->query("SELECT * FROM `administers` WHERE `phone` = '$phone' AND `password` = '$password';");
        if ($row = $result->fetch_assoc())
        {
            return $row["id"];
        }
        return 0;
    }
    public function CreatePrivateAuthToken($id)
    {
        $token = $this->_GenerateToken();
        $expires = time() + 24 * 60 * 60;
        if ($this->query("INSERT INTO `adm_auth` (`token`, `user`, `expires`) VALUES ('$token', '$id', '$expires')")) 
                return $token;
        return false;
    }
    public function CheckPrivateToken($arguments)
    {
        //var_dump($arguments);
        $id = $this->_GetPrivateUserId($arguments[0]);
        $token = $arguments[1];
        $sql = "SELECT * FROM `adm_auth` WHERE `user` = '$id' AND `token` = '$token';";
        if ($result = $this->query($sql))
            if ($result->num_rows > 0) return true;
        return false;
    }
    public function LoadGatesList()
    {
        $return = array();
        $sql = "SELECT * FROM `gates` WHERE 1";
        if ($result = $this->query($sql))
        {
            while ($row = $result->fetch_assoc())
            {
                $return[] = array("id" => $row['id'], "url" => $row['url']);
            }
        }
        return $return;
    }
    public function LoadUsersList()
    {
        $return = array();
        $sql = "SELECT * FROM `users` WHERE 1";
        if ($result = $this->query($sql))
        {
            while ($row = $result->fetch_assoc())
            {
                $return[] = array(
                    "id" => $row['id'],
                    "phone" => $row['phone'],
                    "state" => $row['rq_state']);
            }
        }
        return $return;
    }
    public function LoadGateData($gate_id)
    {
        $return = array();
        $gate_data = false;
        $gate_dongles = false;
        if ($result = $this->query("SELECT * FROM `gates` WHERE `id` = '$gate_id'"))
        {
            if ($row = $result->fetch_assoc())
            {
                $gate_data = $row;
            }
            $result->close();
        }
        if ($result = $this->query("SELECT * FROM `dongles` WHERE `gate_id` = '$gate_id'"))
        {
            $gate_dongles = array();
            while ($row = $result->fetch_assoc())
            {
                $gate_dongles[] = $row;
            }
            $result->close();
        }
        if ($gate_data && $gate_dongles)
        {
            $return = array("data" => $gate_data, "dongles" => $gate_dongles);
        }
        return $return;
    }
    public function CheckPublicCredentials($arguments)
    {
        $phone = $arguments["phone"];
        $password = $arguments["pass"];
        $result = $this->query("SELECT * FROM `users` WHERE `phone` = '$phone' AND `password` = '$password';");
        if ($row = $result->fetch_assoc())
        {
            return $row["id"];
        }
        return 0;
    }
    public function CreatePublicAuthToken($id)
    {
        $token = $this->_GenerateToken();
        $expires = time() + 24 * 60 * 60;
        if ($this->query("INSERT INTO `auth` (`token`, `user`, `expired`) VALUES ('$token', '$id', '$expires')")) 
                return $token;
        return false;
    }
    public function CheckPublicToken($arguments)
    {
        //var_dump($arguments);
        $id = $this->_GetPublicUserId($arguments[0]);
        $token = $arguments[1];
        $sql = "SELECT * FROM `auth` WHERE `user` = '$id' AND `token` = '$token';";
        //echo "sql = $sql";
        if ($result = $this->query($sql))
            if ($result->num_rows > 0) return true;
        return false;
    }
    public function LoadPublicUserData($arguments)
    {
        $return = false;
        $phone = $arguments[0];
        $sql = "SELECT * FROM `users` WHERE `phone` = '$phone';";
        if ($result = $this->query($sql))
            if ($row = $result->fetch_assoc())
            {
                $return = array(
                    "id" => $row["id"],
                    "phone" => $row["phone"],
                    "status" => $row['rq_state'],
                    "name"	 => $row['name'],
                    "second_name" => $row['second_name'],
                    "last_name"	 => $row['last_name'],
                    "account" => $row['account'],
                    "email" => $row['email']
                );
            }
        return $return;
    }
    public function ToggleFlagPublic($arguments)
    {
        $time = time();
        $phone = $arguments[0];
        $sql = "UPDATE `users` SET `rq_state` = IF(`rq_state` = 'up', 'down', 'up'), `last_status_at` = '$time' WHERE `phone` = '$phone'";
        if ($this->query($sql)) return true;
        return false;
    }
    public function LoadUserTransactions($arguments)
    {
        $return = array();
        $id = $this->_GetPublicUserId($arguments[0]);
        $sql = "SELECT * FROM `transactions` WHERE `from` = '$id' OR `to` = '$id' ORDER BY `time` DESC ;";
        if ($result = $this->query($sql))
        {
            while ($transaction = $result->fetch_assoc())
            {
                $return[] = $transaction;
            }
        }
        return $return;
        
    } 
	public function FillUpBalance($arguments)
	{
		$phone = $arguments[0];
		$token = $arguments[1];
		$value = $arguments[2];
		$id = $this->_GetPublicUserId($phone);
		$this->_CreateTransaction("200", $id, $value, "TEST_FILL_UP($value)");
	}
	public function LoadUserCalls($arguments)
	{
        $return = array();
        $id = $this->_GetPublicUserId($arguments[0]);
        $sql = "SELECT `calls`.`id` AS `id`, `dongles`.`phone` AS `phone`, `calls`.`caller`, `calls`.`result`, `calls`.`answer_length`, `calls`.`created_on` FROM `calls` INNER JOIN `dongles` ON `calls`.`user_dongle` = `dongles`.`id` WHERE `user_id` = '$id' ORDER BY `calls`.`created_on` DESC ";
        if ($result = $this->query($sql))
        {
            while ($call = $result->fetch_assoc())
            {
                $return[] = $call;
            }
        }
        return $return;
	}
    public function LoadWaitingUsers()
    {
        $return = array();
        $sql = "SELECT * FROM `users` WHERE `rq_state` = 'up' ORDER BY `last_status_at` ASC ;";
        if ($result = $this->query($sql))
            while ($row = $result->fetch_assoc())
            {
                $return[] = $row["id"];
            }
        return $return;
    }
    public function GetDongles($id)
    {
        $return = array();
        $sql = "SELECT * FROM `dongles` WHERE `gate_id` = '$id';";
        if ($result = $this->query($sql))
            while ($row = $result->fetch_assoc())
            {
                $return[] = $row["id"];
            }
        return $return;
    }
    public function SaveCall($dial_hash, $cdn, $cph, $udn, $uid, $dir, $rg)
    {
        $hash = $dial_hash;
        $user_id = $uid;
        $user_dongle = $udn;
        $client_dongle = $cdn;
        $client_phone = $cph;
        $gate_id = 1;
        $caller = ($dir === 1000) ? "USER" : (($dir === 1001) ? "CLIENT" : "UNKNOWN");
		if ($rg) $caller = "ROUTEGEN";
        $created_on = time();
        $sql = <<< SQL
INSERT INTO 
`calls` 
(
    `hash`, 
    `user_id`, 
    `user_dongle`, 
    `client_dongle`, 
    `client_phone`, 
    `gate_id`, 
    `caller`, 
    `created_on`
) VALUES (
    '$hash',
    '$user_id',
    '$user_dongle',
    '$client_dongle',
    '$client_phone',
    '$gate_id',
    '$caller',
    '$created_on'
);        
SQL;
        $this->query($sql);
        if ($caller == "CLIENT")
        {
            $sql = "UPDATE `users` SET `rq_state` = IF(`rq_state` = 'up', 'hold', 'down') WHERE `id` = '$user_id'";
            $this->query($sql);
        }
    }
    public function UpdateCall($dial_hash, $dialtime, $answertime, $result)
    {
        $this->query("UPDATE `calls` SET `result` = '$result', `dial_length` = '$dialtime', `answer_length` = '$answertime' WHERE `hash` = '$dial_hash';");
		
		$result1 = $this->query("SELECT * FROM `calls` WHERE `hash` = '$dial_hash';");
        if ($call = $result1->fetch_assoc())
		{
			if ($call['caller'] == "ROUTEGEN")
			{
				$uid = $call['user_id'];
				$time = time();
				
				switch($result)
				{
					case 1000://STATUS_ANSWER
						$this->query("UPDATE `users` SET `rq_state` = 'down', `last_status_at` = '$time' WHERE `id` = '$uid'");
					break;
					case   1002://STATUS_CONGESTION
						if ($answeredtime > 0) $this->query("UPDATE `users` SET `rq_state` = 'down', `last_status_at` = '$time' WHERE `id` = '$uid'");
						else $this->query("UPDATE `users` SET `rq_state` = 'up' WHERE `id` = '$uid'");
					break;
					case   1003://STATUS_NOANSWER
					case   1004://STATUS_BUSY
					case   1005://STATUS_CANCEL
						$this->query("UPDATE `users` SET `rq_state` = 'up', `last_status_at` = '$time' WHERE `id` = '$uid'");
					break;
					default:
						$this->query("UPDATE `users` SET `rq_state` = 'up' WHERE `id` = '$uid'");
					break;
				}
			}			
		}
		$this->_BillOutCall($dial_hash);
	}
    public function RegisterGate($settings)
    {
        $id = $settings['id'];
        $url = $settings['url'];
        $this->query("INSERT INTO `gates` (`id`, `url`) VALUES ('$id', '$url')");
    }
    public function UserExists($phone)
    {
        $result = $this->query("SELECT * FROM `users` WHERE `phone` = '$phone';");
        if ($result->fetch_assoc()) return true;
        return false;
    }
    public function RegisterUser($phone)
    {
        $password = $this->_GeneratePassword();
        $pswhash = md5(md5($password));
        $this->query("INSERT IGNORE INTO `users` (`phone`, `password`) VALUES ('$phone', '$pswhash')");
        return $password;
    }
    public function IsRouteExists($user, $dongle)
    {
        $result = $this->query("SELECT * FROM `routes` WHERE `user_id` = '$user' AND `user_dongle` = '$dongle' ");
        if ($result->fetch_assoc()) return true;
        return false;
    }
    public function SaveRoute($uid, $udn, $cdn, $cph)
    {
        $sql = <<< SQL
INSERT IGNORE INTO `routes`
(
    `user_id`,
    `user_dongle`,
    `client_dongle`,
    `client_phone`,
    `gate_id`
) VALUES (
    '$uid', 
    '$udn', 
    '$cdn', 
    '$cph',
    '1'
)
SQL;
        $this->query($sql);		
	}
    private function _GenerateToken()
    {
        return $this->_GenerateString("abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789",
            32);
    }
    private function _GeneratePassword()
    {
        return $this->_GenerateString("abcdefghijklmnopqrstuvwxyz0123456789", 8);
    }
    private function _GetPrivateUserId($phone)
    {
        if ($result = $this->query("SELECT * FROM `administers` WHERE `phone` = '$phone';"))
        {
            if ($row = $result->fetch_assoc()) return $row['id'];
        }
        return 0;
    }
    private function _GetPublicUserId($phone)
    {
        if ($result = $this->query("SELECT * FROM `users` WHERE `phone` = '$phone';"))
        {
            if ($row = $result->fetch_assoc()) return $row['id'];
        }
        return 0;
    }
    private function _GenerateString($source, $length)
    {
        $result = "";
        $count = strlen($source);
        for ($i = 0; $i < $length; $i++)
        {
            $result .= substr($source, rand(0, $count), 1);
        }
        return $result;
    }
	private function _BillOutCall($hash)
	{
		$result1 = $this->query("SELECT * FROM `calls` WHERE `hash` = '$hash';");
        if ($call = $result1->fetch_assoc())
		{
			if ($call['result'] == 1000)
			{
				if ($call['caller'] == "ROUTEGEN")
				{
					$this->_CreateTransaction($call["user_id"], "100", 300.0, "ON_DEMAND($hash)");
				}
				else
				{
					if ($call["answer_length"]>5)
					{
						$amnt = ceil($call["answer_length"]/60.0);//round($call["answer_length"]/60.0, 2);
						$this->_CreateTransaction($call["user_id"], "100", $amnt, "PREPARED_ROUTE($hash)");
					}
				}
			}
		}
	}
	private function _CreateTransaction($from, $to, $amount, $desc)
	{
		$from_bal = $this->_GetUserBalance($from);
		$to_bal = $this->_GetUserBalance($to);
		$from_bal -= $amount;
		$to_bal += $amount;
        $time = time();
		$this->multi_query(
		"INSERT INTO `transactions` (`from`, `to`, `amount`, `time`, `description`) VALUES ('$from', '$to', '$amount', '$time', '$desc'); 
		UPDATE `users` SET `account` = '$from_bal' WHERE `id` = '$from'; 
		UPDATE `users` SET `account` = '$to_bal' WHERE `id` = '$to';");
	}	
    private function _GetUserBalance($uid)
    {
        $result = $this->query("SELECT * FROM `users` WHERE `id` = '$uid';");
        if ($row = $result->fetch_assoc()) return $row['account'];
        return -100;
    }
}
