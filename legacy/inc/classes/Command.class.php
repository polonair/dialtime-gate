<?php

class Command
{
    # Constants
    const COM_PING = "ping";
    const COM_TOUCH = "touch";
    const COM_PRIVATE_EXEC = "private";
    const COM_PUBLIC_EXEC = "public";

    # Fields
    private $_Url = "NONE";
    private $_Command;
    private $_Data;

    # Properties
    public function getCommand()
    {
        return $this->_Command;
    }
    public function getData()
    {
        return $this->_Data;
    }

    # Methods
    public function __construct($command, $data, $url = "NONE")
    {
        $this->_Url = $url;
        $this->_Command = $command;
        $this->_Data = $data;
    }
    public function Send()
    {
        if ($this->_Url === "NONE") return;
        $packet = $this->_CreatePacket();
        syslog(LOG_DEBUG, "sending packet: [$packet]");
        $ch = curl_init("http://" . $this->_Url . "/");
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $packet);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        $response = curl_exec($ch);
        curl_close($ch);
        $response = json_decode(base64_decode($response), true);
        return $response;
    }
    private function _CreatePacket()
    {
        $return = array("command" => $this->_Command, "data" => $this->_Data);
        $return = base64_encode(json_encode($return));
        return $return;
    }
    public static function Load()
    {
        $packet = file_get_contents("php://input");
        $packet = json_decode(base64_decode($packet), true);
        return new Command($packet["command"], $packet["data"]);
    }
}
