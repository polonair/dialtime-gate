<?php

class User
{
	# Methods
    public static function GetPhone($userId)
    {
        $row = GDB::select("phone")->from("users")->where("id")->opEQ($userId)->exe();
        if (count($row) > 0) return $row[0]["phone"];
        return "";
    }
}
