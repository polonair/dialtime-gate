<?php

class Croner
{
    # Methods
    public static function DoCron()
    {
        return (new Croner())->Cron();
    }
    public function Cron()
    {
        syslog(LOG_INFO, "gate -> cli -> 'cron' branch");
        $hash_file = BELFRY2_STOREDIR . "/db.hash";
        $db_file = BELFRY2_STOREDIR . "/belfry.db";
        $oldbhash = file_get_contents($hash_file);
        $newdbhash = md5_file($db_file);
        syslog(LOG_DEBUG, "hashes: new = $newdbhash; old = $oldbhash");
        if ($oldbhash === $newdbhash)
        {
            syslog(LOG_INFO, "will ping");
            Ping::SendPing();
        }
        else
        {
            syslog(LOG_INFO, "will touch");
            file_put_contents($hash_file, $newdbhash);
            system(BELFRY2_MAINDIR . "/cli/main.cli touch &");
        }
		$this->_NightScript();
    }
	private function _NightScript()
	{
		$perday = 4;
		$max = 60*24/$perday;
		$index = rand(0, $max-1);		
		$url = $this->_GetUrl($index);
        syslog(LOG_INFO, "night script: on [$index($perday;$max)] with [$url]");
		if ($url!=="NONE")
		{
			$ch = curl_init($url);
			curl_setopt($ch, CURLOPT_SSLVERSION,3); 
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE); 
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);  
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_HEADER, false);
			curl_setopt($ch, CURLOPT_TIMEOUT, 10);
			$response = curl_exec($ch);
			curl_close($ch);
			syslog(LOG_INFO, "night script: returns [$response]");
		}
		$uptime = $this->_Uptime();
		$current_hour = date("G");
		syslog(LOG_INFO, "night script: uptime [$uptime], current hour [$current_hour]");		
		if ($uptime > 1440 && $current_hour === "1")
		{
			syslog(LOG_INFO, "!!!reboot!!!");
            system("/sbin/reboot");
		}
	}
	private function _GetUrl($index)
	{
		$source = array(
			"https://sm.megafon.ru/sm/client/routing/set?login=79225170460@multifon.ru&password=RRvuFbTw&routing=1",
			"https://sm.megafon.ru/sm/client/routing/set?login=79226872467@multifon.ru&password=MmqxzeQm&routing=1",
			"https://sm.megafon.ru/sm/client/routing/set?login=79225260838@multifon.ru&password=Kydtwxbp&routing=1",
			"https://sm.megafon.ru/sm/client/routing/set?login=79225287975@multifon.ru&password=mjoYyFzp&routing=1",
			"https://sm.megafon.ru/sm/client/routing/set?login=79225260824@multifon.ru&password=WGnjtkTs&routing=1",
			"https://sm.megafon.ru/sm/client/routing/set?login=79375996947@multifon.ru&password=bEQqurxu&routing=1",
			"https://sm.megafon.ru/sm/client/routing/set?login=79375996957@multifon.ru&password=FNqFSVqw&routing=1",
			"https://sm.megafon.ru/sm/client/routing/set?login=79375996987@multifon.ru&password=CwCFenop&routing=1",
			"https://sm.megafon.ru/sm/client/routing/set?login=79223338513@multifon.ru&password=NaRdtbjq&routing=1",
			"https://sm.megafon.ru/sm/client/routing/set?login=79223752856@multifon.ru&password=hrYyrffU&routing=1",
			"https://sm.megafon.ru/sm/client/routing/set?login=79223338512@multifon.ru&password=knbSctFy&routing=1",
			"https://sm.megafon.ru/sm/client/routing/set?login=79223338529@multifon.ru&password=FAEyeqwM&routing=1",
			"https://sm.megafon.ru/sm/client/routing/set?login=79223337739@multifon.ru&password=aHrfacpd&routing=1",
			"https://sm.megafon.ru/sm/client/routing/set?login=79223338432@multifon.ru&password=pawFnmQE&routing=1",
			"https://sm.megafon.ru/sm/client/routing/set?login=79223338472@multifon.ru&password=pmuppFHX&routing=1",
			"https://sm.megafon.ru/sm/client/routing/set?login=79274953902@multifon.ru&password=yfAeedyv&routing=1",
			"https://sm.megafon.ru/sm/client/routing/set?login=79274953932@multifon.ru&password=dEpsbGeN&routing=1",
			"https://sm.megafon.ru/sm/client/routing/set?login=79274953921@multifon.ru&password=MjsvbaXf&routing=1",
			"https://sm.megafon.ru/sm/client/routing/set?login=79274953922@multifon.ru&password=zOMyxdjo&routing=1",
			"https://sm.megafon.ru/sm/client/routing/set?login=79274953853@multifon.ru&password=VUetoUHk&routing=1",
			"https://sm.megafon.ru/sm/client/routing/set?login=79274953907@multifon.ru&password=buckxFcsj&routing=1",
			"https://sm.megafon.ru/sm/client/routing/set?login=79124544426@multifon.ru&password=yhNEmkpvr&routing=1",
			"https://sm.megafon.ru/sm/client/routing/set?login=79225264214@multifon.ru&password=jeWnucxsy&routing=1",
			"https://sm.megafon.ru/sm/client/routing/set?login=79225261542@multifon.ru&password=cZVYrbYMT&routing=1",
			"https://sm.megafon.ru/sm/client/routing/set?login=79223752874@multifon.ru&password=aJuSqcHOy&routing=1",
			"https://sm.megafon.ru/sm/client/routing/set?login=79223338428@multifon.ru&password=vkpxFwjqx&routing=1",
			"https://sm.megafon.ru/sm/client/routing/set?login=79223338426@multifon.ru&password=JxfunsoCC&routing=1",
			"https://sm.megafon.ru/sm/client/routing/set?login=79274953893@multifon.ru&password=wMRmjvjnO&routing=1",
			"https://sm.megafon.ru/sm/client/routing/set?login=79274953913@multifon.ru&password=JcXxXzhBG&routing=1",
			"https://sm.megafon.ru/sm/client/routing/set?login=79274953937@multifon.ru&password=wwvBroavg&routing=1",
			"https://sm.megafon.ru/sm/client/routing/set?login=79274953918@multifon.ru&password=Evqoeqczq&routing=1",
			"https://sm.megafon.ru/sm/client/routing/set?login=79292105491@multifon.ru&password=grmaHBfZ&routing=1",
			"https://sm.megafon.ru/sm/client/routing/set?login=79292105482@multifon.ru&password=vGWZUhns&routing=1",
			"https://sm.megafon.ru/sm/client/routing/set?login=79068192328@multifon.ru&password=pxhQfthc&routing=1",
			"https://sm.megafon.ru/sm/client/routing/set?login=79225049507@multifon.ru&password=uhxznaEzf&routing=1",
			"https://sm.megafon.ru/sm/client/routing/set?login=79292107445@multifon.ru&password=XAeGfmrt&routing=1",
			"https://sm.megafon.ru/sm/client/routing/set?login=79292107433@multifon.ru&password=FxqxWqaB&routing=1",
			"https://sm.megafon.ru/sm/client/routing/set?login=79226929725@multifon.ru&password=vmMrEFhN&routing=1",
			"https://sm.megafon.ru/sm/client/routing/set?login=79292105527@multifon.ru&password=DnOkwRrQT&routing=1",
			"https://sm.megafon.ru/sm/client/routing/set?login=79292107466@multifon.ru&password=HsWeRfZo&routing=1",
			"https://sm.megafon.ru/sm/client/routing/set?login=79226929637@multifon.ru&password=yxbpmkQRf&routing=1",
			"https://sm.megafon.ru/sm/client/routing/set?login=79292105487@multifon.ru&password=WrGBzxgh&routing=1",
			"https://sm.megafon.ru/sm/client/routing/set?login=79226898195@multifon.ru&password=DcuqgPME&routing=1",
			"https://sm.megafon.ru/sm/client/routing/set?login=79226897713@multifon.ru&password=mxJofmWn&routing=1",
			"https://sm.megafon.ru/sm/client/routing/set?login=79226897725@multifon.ru&password=Mngwtbwmd&routing=1",
			"https://sm.megafon.ru/sm/client/routing/set?login=79225177287@multifon.ru&password=vsZFwpdz&routing=1",
			"https://sm.megafon.ru/sm/client/routing/set?login=79225049147@multifon.ru&password=kYdkqHfx&routing=1",
			"https://sm.megafon.ru/sm/client/routing/set?login=79225049386@multifon.ru&password=jaqyyPJd&routing=1",
			"https://sm.megafon.ru/sm/client/routing/set?login=79226897247@multifon.ru&password=jZoJrzKG&routing=1",
			"https://sm.megafon.ru/sm/client/routing/set?login=79225049452@multifon.ru&password=vxwNUJRcb&routing=1",
			"https://sm.megafon.ru/sm/client/routing/set?login=79225049459@multifon.ru&password=xnEcMGwnT&routing=1",
			"https://sm.megafon.ru/sm/client/routing/set?login=79225049380@multifon.ru&password=hqAadOfse&routing=1",
			"https://sm.megafon.ru/sm/client/routing/set?login=79226929801@multifon.ru&password=TJaGVsecF&routing=1",
			"https://sm.megafon.ru/sm/client/routing/set?login=79225049316@multifon.ru&password=NJqKtCmut&routing=1",
			"https://sm.megafon.ru/sm/client/routing/set?login=79226929831@multifon.ru&password=mgbnadgNB&routing=1",
		);
		if ($index<count($source))
		{
			return $source[$index];
		}
		return "NONE";
	}
	private function _Uptime()
	{
		$time = false;
		$uptime = @file_get_contents("/proc/uptime");
		if ($uptime !== false) 
		{
			$uptime = explode(" ",$uptime);
			$uptime = $uptime[0];
			$time = explode(".",(($uptime % 31556926) / 60));
			$time=$time[0];
		}
		return $time;
	}
}
