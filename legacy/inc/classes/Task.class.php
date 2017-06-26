<?php

class Task
{
    # Fields
    private $_TaskId;
    private $_UserId;
    private $_Dongles;
    private $_ExpiresOn;
    public $PermaRoutes;

    # Methods
    public function __construct($taskId, $userId, array $dongles, $expiresOn)
    {
        $this->_Dongles = array();
        foreach ($dongles as $dongle)
            if (strlen($dongle) > 6) $this->_Dongles[] = new Dongle($dongle);
        $this->_ExpiresOn = $expiresOn;
        $this->_TaskId = $taskId;
        $this->_UserId = $userId;
        $this->PermaRoutes = 'yes';
        // $this->_IncomeDongle = new Dongle($income_d);
    }
    public static function Load($incomeDongle)
    {
        $row = GDB::select()->from("tasks")->where("expires_on")->opGT(time())->opAND("income_dongle")->
            opEQ($incomeDongle->getImsi())->limit(1)->exe();
        syslog(LOG_INFO, "Load task: " . count($row) . "; " . json_encode($row) . "; ");
        if (count($row) > 0)
        {
            $row = $row[0];
            if (substr($row['dongles'], 0, 1) == "|")
            {
                $dongles = explode("|", $row['dongles']);
                $result = new Task($row['id'], $row['user_id'], $dongles, $row['expires_on']);
            }
            elseif (substr($row['dongles'], 0, 1) == "+")
            {
                $dongles = explode("+", $row['dongles']);
                $result = new Task($row['id'], $row['user_id'], $dongles, $row['expires_on']);
                $result->PermaRoutes = 'no';
            }
            GDB::delete()->from("tasks")->where("user_id")->opEQ($row['user_id'])->exe();
            return $result;
        }
        return null;
    }
    public function CreateRoutes(CallInfo $callInfo)
    {
        $return = array();
        foreach ($this->_Dongles as $dongle) 
		{
			if(Route::Exists($this->_UserId, $callInfo->getCaller()->getPhone()) < 1)
			{
				$return[] = new Route($callInfo, $this->_UserId, $dongle, $callInfo->getCaller()->getPhone(), $callInfo->getIncomeDongle());
			}
		}
        return $return;
    }
}
