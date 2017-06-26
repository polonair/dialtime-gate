<?php

class CommonApi
{
    # Methods
    protected function LoadAttentions($arguments, $group)
    {
        return array();
        # return array("Внимание! До 4 ноября сервис target-call.ru работает в тестовом режиме.");
    }
    protected function IsTokenValid($arguments, $group)
    {
        syslog(LOG_INFO, "XC: IsTokenValid");
        $user = SDB::select(array("id" => "users.id"))->from("users")->inner_join("$group")->
            on(array("users.id" => "$group.id"))->where("phone")->opEQ($arguments[0])->exe();
        syslog(LOG_DEBUG, "XC: got " . json_encode($user) . " count: " . count($user));
        if (count($user) === 1)
        {
            $id = $user[0]["id"];
            $token = $arguments[1];
            if (SDB::select()->from("auth")->where("user")->opEQ($id)->opAND("token")->opEQ
                ($token)->count() > 0) return true;
        }
        return false;
    }
    protected function GetToken($arguments, $group)
    {
        syslog(LOG_INFO, "XC: GetToken");
        $user = SDB::select(array("id" => "users.id"))->from("users")->inner_join("$group")->
            on(array("users.id" => "$group.id"))->where("phone")->opEQ($arguments[0])->
            opAND("password")->opEQ($arguments[1])->exe();
        if (count($user) > 0)
        {
            $token = $this->_GenerateToken();
            $expires = time() + 24 * 60 * 60;
            if (SDB::insert()->into("auth")->values(array(
                "token" => $token,
                "user" => $user[0]["id"],
                "expired" => $expires))->exe()) return $token;
        }
        return false;
    }
    protected function Register($arguments, $group)
    {
        syslog(LOG_INFO, "XC: Register args: " . json_encode($arguments) . "; group: $group");
        $phone = $arguments[0];
        $password = $this->_GeneratePassword();
        $pswhash = md5(md5($password));
        $result = false;
        if ($group == "clients") $result = SDB::func("register_client", array($phone, $pswhash))->
                exe()[0]["result"];
        elseif ($group == "partners") $result = SDB::func("register_partner", array($phone,
                    $pswhash))->exe()[0]["result"];
        if ($result == "OK")
        {
            Smsc::SendPasswordR($phone, $password);
            if ($group == "clients") Smsc::AlertOnNewUser($phone);
        }
        return $result;
    }
    protected function ResetPassword($arguments, $group)
    {
        syslog(LOG_INFO, "XC: ResetPassword");
        $phone = $arguments[0];
        $password = $this->_GeneratePassword();
        $pswhash = md5(md5($password));
        $result = false;
        if ($group == "clients" || $group == "partners") $result = SDB::func("reset_password",
                array($phone, $pswhash))->exe()[0]["result"];
        if ($result == "OK") Smsc::SendPasswordR($phone, $password);
        return $result;
    }
    protected function RequestIsAuthorized($phone, $token, $group)
    {
        $user = SDB::select(array("id" => "users.id"))->from("users")->inner_join($group)->
            on(array("users.id" => "$group.id"))->where("phone")->opEQ($phone)->exe();
        if (count($user) > 0)
        {
            $user = $user[0];
            $id = $user["id"];
            if (SDB::select()->from("auth")->where("user")->opEQ($id)->opAND("token")->opEQ
                ($token)->count() > 0) return $id;
        }
        return false;
    }
    protected function LoadCategories($arguments, $group)
    {
        syslog(LOG_INFO, "XC: LoadCategories");
        if ($this->RequestIsAuthorized($arguments[0], $arguments[1], $group))
        {
            $categories = SDB::select(array(
                "id" => "categories1.id",
                "name" => "categories1.name",
                "description" => "categories1.description",
                "root_id" => "categories1.root",
                "root_name" => "categories2.name"))->from("categories")->as_("categories1")->
                left_outer_join("categories")->as_("categories2")->on(array("categories1.root" =>
                    "categories2.id"))->exe();
            if (count($categories) > 0) return $categories;
        }
        return false;
    }
    protected function LoadLocations($arguments, $group)
    {
        syslog(LOG_INFO, "XC: LoadLocations");
        if ($this->RequestIsAuthorized($arguments[0], $arguments[1], $group))
        {
            $locations = SDB::select(array(
                "id" => "locations1.id",
                "name" => "locations1.name",
                "description" => "locations1.description",
                "root_id" => "locations1.root",
                "root_name" => "locations2.name"))->from("locations")->as_("locations1")->
                left_outer_join("locations")->as_("locations2")->on(array("locations1.root" =>
                    "locations2.id"))->exe();
            if (count($locations) > 0) return $locations;
        }
        return false;
    }
    private function _GenerateToken()
    {
        return $this->_GenerateString("abcdefghijklmnopqrstuvwxyz" .
            "ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789", 32);
    }
    private function _GeneratePassword()
    {
        return $this->_GenerateString("abcdefghijklmnopqrstuvwxyz0123456789", 8);
    }
    private function _GenerateString($source, $length)
    {
        $result = "";
        $count = strlen($source);
        for ($i = 0; $i < $length; $i++) $result .= substr($source, rand(0, $count), 1);
        return $result;
    }
}
