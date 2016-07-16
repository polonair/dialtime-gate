<?php

namespace Polonairs\Dialtime\GateBundle\Cron;

use Doctrine\Bundle\DoctrineBundle\Registry as Doctrine;

class Dongler
{
	private $doctrine = null;
	private $file_name;
	//private $file_name = "./sip.conf";

	public function __construct(Doctrine $doctrine, $sip_conf)
	{
		$this->doctrine = $doctrine;
		$this->file_name = $sip_conf;
	}
	public function update()
	{
		$em = $this->doctrine->getManager();
		$dongles = $em->getRepository("GateBundle:Dongle")->loadActive();
		$new_content = $this->createContent($dongles);
		$old_content = $this->findContent();
		if ($new_content !== $old_content)
		{
			$this->saveContent($new_content);
			return true;
		}
		return false;
	}
	private function createContent(array $dongles)
	{
		$part1 = 
			"[general]\r\n\r\n" .
			"tcpenable=yes\r\n" .
			"allow=all\r\n" .
			"allowguest=no\r\n";
		$part2 = "";

		foreach($dongles as $id => $dongle)
		{
			$number = $dongle->getNumber();
			$voice_pass = $dongle->getVoicePassword();
			$part1 .= sprintf(
				"register => %s@multifon.ru:%s:%s@sbc.megafon.ru:5060/%s ; id:%d\r\n",
				$number,
				$voice_pass,
				$number,
				$number,
				$id);

			$part2 .= sprintf(
				";-----------------------------------------------------------------------\r\n\r\n" .
				"[%s] ; id:%d\r\n\r\n" .
				"dtmfmode=inband\r\n" .
				"username=%s\r\n" .
				"type=peer\r\n" .
				"secret=%s\r\n" .
				"host=sbc.megafon.ru\r\n" .
				"fromuser=%s\r\n" .
				"fromdomain=multifon.ru\r\n" .
				"port=5060\r\n" .
				"nat=yes\r\n" .
				"context=income\r\n" .
				"insecure=port,invite\r\n",				
				$number,
				$id,
				$number,
				$voice_pass,
				$number);
		}

		return $part1 . $part2;
	}
	private function findContent()
	{
		if (file_exists($this->file_name))
			return file_get_contents($this->file_name);
		else 
			return "";
	}
	private function saveContent($new_content)
	{
		file_put_contents($this->file_name, $new_content);
	}
}
