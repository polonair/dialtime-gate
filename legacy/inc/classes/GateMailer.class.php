<?php

class GateMailer
{
	public static function SendCallReport()
	{
		$str_num = 50;
        $datas = array();
        exec("tail -n$str_num /var/log/belfry2.log", $datas);
		$message = " начало \r\n";
		foreach($datas as $str) $message.=$str."\r\n";
		/**/
		//tQ4BP9Fc
        $transport = Swift_SmtpTransport::newInstance('smtp.timeweb.ru', 465, 'ssl') #
            ->setUsername('noreply@target-call.ru') #
            ->setPassword('1Ue36kzr');
        $mailer = Swift_Mailer::newInstance($transport);

        $attachment = Swift_Attachment::newInstance() #
            ->setFilename('logs.txt') #
            ->setContentType('text/plain') #
            ->setBody($message);
        $message = Swift_Message::newInstance() #
            ->setSubject("Уведомление о звонке") #
            ->setFrom(array('noreply@target-call.ru' => 'Target-Call')) #
            ->setTo(array("fixnim@gmail.com" => "Администратор")) #
            ->setBody("Получен звонок, последние $str_num строк лога в приложении.") #
            ->attach($attachment);
        $mailer->send($message);
		
	}
}