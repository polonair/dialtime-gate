<?php

class Mailer
{
    public static function SendRecord($hash, $created, $phone, $name, $email)
    {
        $filepath = BELFRY2_STOREDIR . "/records/call_$hash.mp3";
        if (file_exists($filepath))
        {
            $transport = Swift_SmtpTransport::newInstance('smtp.timeweb.ru', 465, 'ssl') #
                ->setUsername('recorder@target-call.ru') #
                ->setPassword('kDVIdZlt');
            $mailer = Swift_Mailer::newInstance($transport);
            $ts = date("d.m.Y H:i", $created);
            $filename = "rec_" . date("d_m_Y_H_i", $created) . ".mp3";
            $body = <<< MSG
$name, направляем Вам запись разговора
с абонентом $phone от $ts. 
Аудиофайл с разговором находится в приложении к этому письму.

---

Это письмо было сформировано автоматически по вашему запросу,
не отвечайте на него.

MSG;
            $message = Swift_Message::newInstance() #
                ->setSubject("Запись от $ts") #
                ->setFrom(array('recorder@target-call.ru' => 'Target-Call')) #
                ->setTo(array($email => $name)) #
                ->setBody($body) #
                ->attach(Swift_Attachment::fromPath($filepath)->setFilename($filename));
            $mailer->send($message);
            return "OK";
        }
        return "FILE_NOT_FOUND";
    }
    public static function SendSupportFromUser($subject, $message, $from)
    {
        $transport = Swift_SmtpTransport::newInstance('smtp.timeweb.ru', 465, 'ssl') #
            ->setUsername('noreply@target-call.ru') #
            ->setPassword('1Ue36kzr');
        $mailer = Swift_Mailer::newInstance($transport);

        $attachment = Swift_Attachment::newInstance() #
            ->setFilename('message.txt') #
            ->setContentType('text/plain') #
            ->setBody("From: [$from]\r\nSubject: [$subject]\r\nMessage:\r\n>>>>>>>>>>\r\n$message\r\n<<<<<<<<<<\r\n");
        $body = <<< MSG
Получен запрос в службу поддержки от пользователя [$from]
Данные запроса в приложении к письму.

MSG;

        $message = Swift_Message::newInstance() #
            ->setSubject("Запрос в службу поддержки") #
            ->setFrom(array('noreply@target-call.ru' => 'Target-Call')) #
            ->setTo(array("support@target-call.ru" => "Служба поддержки")) #
            ->setBody($body) #
            ->attach($attachment);
        $mailer->send($message);
    }
    public static function SendSupportFromAnonim($subject, $message, $from)
    {
        //tQ4BP9Fc
        $transport = Swift_SmtpTransport::newInstance('smtp.timeweb.ru', 465, 'ssl') #
            ->setUsername('noreply@target-call.ru') #
            ->setPassword('1Ue36kzr');
        $mailer = Swift_Mailer::newInstance($transport);

        $attachment = Swift_Attachment::newInstance() #
            ->setFilename('message.txt') #
            ->setContentType('text/plain') #
            ->setBody("From: [$from]\r\nSubject: [$subject]\r\nMessage:\r\n>>>>>>>>>>\r\n$message\r\n<<<<<<<<<<\r\n");
        $body = <<< MSG
Получен запрос в службу поддержки от анонимного пользователя
Данные запроса в приложении к письму.

MSG;

        $message = Swift_Message::newInstance() #
            ->setSubject("Запрос в службу поддержки") #
            ->setFrom(array('noreply@target-call.ru' => 'Target-Call')) #
            ->setTo(array("support@target-call.ru" => "Служба поддержки")) #
            ->setBody($body) #
            ->attach($attachment);
        $mailer->send($message);
    }
}
