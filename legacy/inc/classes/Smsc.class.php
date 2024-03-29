<?php

/**
 * Examples:
 * 
 * list($sms_id, $sms_cnt, $cost, $balance) = send_sms("79999999999", "Ваш пароль: 123", 1);
 * list($sms_id, $sms_cnt, $cost, $balance) = send_sms("79999999999", "http://smsc.ru\nSMSC.RU", 0, 0, 0, 0, false, "maxsms=3");
 * list($sms_id, $sms_cnt, $cost, $balance) = send_sms("79999999999", "0605040B8423F0DC0601AE02056A0045C60C036D79736974652E72750001036D7973697465000101", 0, 0, 0, 5, false);
 * list($sms_id, $sms_cnt, $cost, $balance) = send_sms("79999999999", "", 0, 0, 0, 3, false);
 * list($cost, $sms_cnt) = get_sms_cost("79999999999", "Вы успешно зарегистрированы!");
 * send_sms_mail("79999999999", "Ваш пароль: 123", 0, "0101121000");
 * list($status, $time) = get_status($sms_id, "79999999999");
 * $balance = get_balance();
 */

class Smsc
{
    # Constants
    const SMSC_LOGIN = "fixnim"; // логин клиента
    const SMSC_PASSWORD = "c789ba64c4200a2f0fc1ef7c29b04609"; // пароль или MD5-хеш пароля в нижнем регистре
    const SMSC_POST = 1; // использовать метод POST
    const SMSC_HTTPS = 0; // использовать HTTPS протокол
    const SMSC_CHARSET = "utf-8"; // кодировка сообщения: utf-8, koi8-r или windows-1251 (по умолчанию)
    const SMTP_FROM = "api@smsc.ru"; // e-mail адрес отправителя

    # Methods
    public static function SendPasswordR($phone, $password)
    {
        Smsc::send_sms( #
            $phone, #
            "Вы успешно зарегистрировались. Ваш пароль: $password.", #
            0, 0, 0, 0, "Target-Call");
    }
    public static function AlertOnNewUser($phone)
    {
        Smsc::send_sms( #
            "+79090512424", #
            "Зарегистрирован новый пользователь с номером [$phone]. Требуется модерация.", #
            0, 0, 0, 0, "Target-Call");
    }

    /**
     * Функция отправки SMS

     * обязательные параметры:
     *     
     * $phones - список телефонов через запятую или точку с запятой
     * $message - отправляемое сообщение
     *     
     * необязательные параметры:
     *     
     * $translit - переводить или нет в транслит (1,2 или 0)
     * $time - необходимое время доставки в виде строки (DDMMYYhhmm, h1-h2, 0ts, +m)
     * $id - идентификатор сообщения. Представляет собой 32-битное число в диапазоне от 1 до 2147483647.
     * $format - формат сообщения (0 - обычное sms, 1 - flash-sms, 2 - wap-push, 3 - hlr, 4 - bin, 5 - bin-hex, 6 - ping-sms, 7 - mms)
     * $sender - имя отправителя (Sender ID). Для отключения Sender ID по умолчанию необходимо в качестве имени
     * передать пустую строку или точку.
     * $query - строка дополнительных параметров, добавляемая в URL-запрос ("valid=01:00&maxsms=3&tz=2")
     * $files - массив путей к файлам для отправки mms-сообщений
     *     
     * возвращает массив (<id>, <количество sms>, <стоимость>, <баланс>) в случае успешной отправки
     * либо массив (<id>, -<код ошибки>) в случае ошибки
     */
    private static function send_sms($phones, $message, $translit = 0, $time = 0, $id =
        0, $format = 0, $sender = false, $query = "", $files = array())
    {
        static $formats = array(
            1 => "flash=1",
            "push=1",
            "hlr=1",
            "bin=1",
            "bin=2",
            "ping=1",
            "mms=1");

        $m = Smsc::_smsc_send_cmd("send", "cost=3&phones=" . urlencode($phones) .
            "&mes=" . urlencode($message) . "&translit=$translit&id=$id" . ($format > 0 ?
            "&" . $formats[$format] : "") . ($sender === false ? "" : "&sender=" . urlencode
            ($sender)) . ($time ? "&time=" . urlencode($time) : "") . ($query ? "&$query" :
            ""), $files);

        if ($m[1] > 0) syslog(LOG_INFO, "Сообщение отправлено успешно. ID: $m[0], всего SMS: $m[1], стоимость: $m[2], баланс: $m[3].");
        else  syslog(LOG_INFO, "Ошибка №" . -$m[1] . ($m[0] ? ", ID: " . $m[0] : ""));

        return $m;
    }

    /**
     * SMTP версия функции отправки SMS
     */
    private static function send_sms_mail($phones, $message, $translit = 0, $time =
        0, $id = 0, $format = 0, $sender = "")
    {
        return mail("send@send.smsc.ru", "", Smsc::SMSC_LOGIN . ":" . Smsc::
            SMSC_PASSWORD . ":$id:$time:$translit,$format,$sender:$phones:$message",
            "From: " . Smsc::SMTP_FROM . "\nContent-Type: text/plain; charset=" . Smsc::
            SMSC_CHARSET . "\n");
    }

    /**
     * Функция получения стоимости SMS
     *     
     * обязательные параметры:
     *    
     * $phones - список телефонов через запятую или точку с запятой
     * $message - отправляемое сообщение
     *    
     * необязательные параметры:
     *     
     * $translit - переводить или нет в транслит (1,2 или 0)
     * $format - формат сообщения (0 - обычное sms, 1 - flash-sms, 2 - wap-push, 3 - hlr, 4 - bin, 5 - bin-hex, 6 - ping-sms)
     * $sender - имя отправителя (Sender ID)
     * $query - строка дополнительных параметров, добавляемая в URL-запрос ("list=79999999999:Ваш пароль: 123\n78888888888:Ваш пароль: 456")
     *     
     * возвращает массив (<стоимость>, <количество sms>) либо массив (0, -<код ошибки>) в случае ошибки
     */
    private static function get_sms_cost($phones, $message, $translit = 0, $format =
        0, $sender = false, $query = "")
    {
        static $formats = array(
            1 => "flash=1",
            "push=1",
            "hlr=1",
            "bin=1",
            "bin=2",
            "ping=1");
        $m = Smsc::_smsc_send_cmd("send", "cost=1&phones=" . urlencode($phones) .
            "&mes=" . urlencode($message) . ($sender === false ? "" : "&sender=" . urlencode
            ($sender)) . "&translit=$translit" . ($format > 0 ? "&" . $formats[$format] : "") .
            ($query ? "&$query" : ""));
        if ($m[1] > 0) syslog(LOG_INFO, "Стоимость рассылки: $m[0]. Всего SMS: $m[1]");
        else  syslog(LOG_INFO, "Ошибка №" . -$m[1]);
        return $m;
    }

    /**
     * Функция проверки статуса отправленного SMS или HLR-запроса

     * $id - ID cообщения
     * $phone - номер телефона
     * $all - вернуть все данные отправленного SMS, включая текст сообщения (0 или 1)

     * возвращает массив:

     * для SMS-сообщения:
     * (<статус>, <время изменения>, <код ошибки доставки>)

     * для HLR-запроса:
     * (<статус>, <время изменения>, <код ошибки sms>, <код IMSI SIM-карты>, <номер сервис-центра>, <код страны регистрации>, <код оператора>,
     * <название страны регистрации>, <название оператора>, <название роуминговой страны>, <название роумингового оператора>)

     * При $all = 1 дополнительно возвращаются элементы в конце массива:
     * (<время отправки>, <номер телефона>, <стоимость>, <sender id>, <название статуса>, <текст сообщения>)

     * либо массив (0, -<код ошибки>) в случае ошибки
     */
    private static function get_status($id, $phone, $all = 0)
    {
        $m = Smsc::_smsc_send_cmd("status", "phone=" . urlencode($phone) . "&id=" . $id .
            "&all=" . (int)$all);
        if ($m[1] != "" && $m[1] >= 0) syslog(LOG_INFO, "Статус SMS = $m[0]" . ($m[1] ?
                ", время изменения статуса - " . date("d.m.Y H:i:s", $m[1]) : ""));
        else  syslog(LOG_INFO, "Ошибка №" . -$m[1]);
        if ($all && count($m) > 9 && (!isset($m[14]) || $m[14] != "HLR")) $m = explode(",",
                implode(",", $m), 9);
        return $m;
    }

    /**
     * Функция получения баланса
     * без параметров
     * возвращает баланс в виде строки или false в случае ошибки
     */
    private static function get_balance()
    {
        $m = Smsc::_smsc_send_cmd("balance"); // (balance) или (0, -error)
        if (!isset($m[1])) syslog(LOG_INFO, "Сумма на счете: " . $m[0]);
        else  syslog(LOG_INFO, "Ошибка №" . -$m[1]);
        return isset($m[1]) ? false : $m[0];
    }
    private static function _smsc_send_cmd($cmd, $arg = "", $files = array())
    {
        $url = (Smsc::SMSC_HTTPS ? "https" : "http") . "://smsc.ru/sys/$cmd.php?login=" .
            urlencode(Smsc::SMSC_LOGIN) . "&psw=" . urlencode(Smsc::SMSC_PASSWORD) .
            "&fmt=1&charset=" . Smsc::SMSC_CHARSET . "&" . $arg;
        $i = 0;
        do
        {
            if ($i)
            {
                sleep(2);
                if ($i == 2) $url = str_replace('://smsc.ru/', '://www2.smsc.ru/', $url);
            }

            $ret = Smsc::_smsc_read_url($url, $files);
        } while ($ret == "" && ++$i < 3);
        if ($ret == "")
        {
            syslog(LOG_INFO, "Ошибка чтения адреса: $url");
            $ret = ",";
        }

        return explode(",", $ret);
    }
    private static function _smsc_read_url($url, $files)
    {
        $ret = "";
        $post = Smsc::SMSC_POST || strlen($url) > 2000;
        if (function_exists("curl_init"))
        {
            static $c = 0; // keepalive

            if (!$c)
            {
                $c = curl_init();
                curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($c, CURLOPT_CONNECTTIMEOUT, 10);
                curl_setopt($c, CURLOPT_TIMEOUT, 60);
                curl_setopt($c, CURLOPT_SSL_VERIFYPEER, 0);
            }

            if ($post || $files)
            {
                list($url, $post) = explode("?", $url, 2);
                curl_setopt($c, CURLOPT_POST, true);
                if ($files)
                {
                    parse_str($post, $m);
                    $post = $m;
                    foreach ($files as $i => $path) $post["file" . $i] = "@" . $path;
                }

                curl_setopt($c, CURLOPT_POSTFIELDS, $post);
            }

            curl_setopt($c, CURLOPT_URL, $url);
            $ret = curl_exec($c);
        }
        elseif ($files)
        {
            syslog(LOG_INFO, "Не установлен модуль curl для передачи файлов");
        }
        else
        {
            if (!Smsc::SMSC_HTTPS && function_exists("fsockopen"))
            {
                $m = parse_url($url);
                if (!$fp = fsockopen($m["host"], 80, $errno, $errstr, 10)) $fp = fsockopen("212.24.33.196",
                        80, $errno, $errstr, 10);
                if ($fp)
                {
                    fwrite($fp, ($post ? "POST $m[path]" : "GET $m[path]?$m[query]") . " HTTP/1.1\r\nHost: smsc.ru\r\nUser-Agent: PHP" .
                        ($post ? "\r\nContent-Type: application/x-www-form-urlencoded\r\nContent-Length: " .
                        strlen($m['query']) : "") . "\r\nConnection: Close\r\n\r\n" . ($post ? $m['query'] :
                        ""));
                    while (!feof($fp)) $ret .= fgets($fp, 1024);
                    list(, $ret) = explode("\r\n\r\n", $ret, 2);
                    fclose($fp);
                }
            }
            else  $ret = file_get_contents($url);
        }

        return $ret;
    }
}
