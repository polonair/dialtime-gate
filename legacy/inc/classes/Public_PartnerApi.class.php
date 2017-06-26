<?php

class Public_PartnerApi extends CommonApi
{
    # Methods
    public function ExecuteCommand($data)
    {
        syslog(LOG_INFO, "outer starts to execute command");
        switch ($data["method"])
        {
            case "Partner::LoadAttentions":
                $result = $this->LoadAttentions($data["arguments"], "partners");
                break;
            case "Partner::GetToken":
                $result = $this->GetToken($data["arguments"], "partners");
                break;
            case "Partner::Register":
                $result = $this->Register($data["arguments"], "partners");
                break;
            case "Partner::IsTokenValid":
                $result = $this->IsTokenValid($data["arguments"], "partners");
                break;
            case "Partner::ResetPassword":
                $result = $this->ResetPassword($data["arguments"], "partners");
                break;
            case "Partner::LoadCategories":
                $result = $this->LoadCategories($data["arguments"], "partners");
                break;
            case "Partner::LoadLocations":
                $result = $this->LoadLocations($data["arguments"], "partners");
                break;
            case "Partner::LoadUser":
                $result = $this->_LoadUser($data["arguments"]);
                break;
            case "Partner::LoadTransactions":
                $result = $this->_LoadTransactions($data["arguments"]);
                break;
            case "Partner::LoadCalls":
                $result = $this->_LoadCalls($data["arguments"]);
                break;
            case "Partner::FillUpBalance":
                $result = $this->_FillUpBalance($data["arguments"]);
                break;
            case "Partner::ChangeUserData":
                $result = $this->_ChangeUserData($data["arguments"]);
                break;
            case "Partner::SearchOffers":
                $result = $this->_SearchOffers($data["arguments"]);
                break;
            case "Partner::RequestOffer":
                $result = $this->_RequestOffer($data["arguments"]);
                break;
            case "Partner::LoadRequestedOffers":
                $result = $this->_LoadRequestedOffers($data["arguments"]);
                break;
            case "Partner::LoadStatistics":
                $result = $this->_LoadStatistics($data["arguments"]);
                break;
        }
        $result = json_encode($result);
        syslog(LOG_INFO, "OXC result jsoned: $result");
        echo base64_encode($result);
    }
    private function _LoadTransactions($arguments)
    {
        syslog(LOG_INFO, "OXC: LoadTransactions");
        $user = SDB::select()->from("users")->where("phone")->opEQ($arguments[0])->exe();
        if (count($user) > 0)
        {
            $user = $user[0];
            $id = $user["id"];
            $token = $arguments[1];
            if (SDB::select()->from("auth")->where("user")->opEQ($id)->opAND("token")->opEQ
                ($token)->count() > 0)
            {
                $transactions = SDB::select()->from("transactions")->where("from")->opEQ($id)->
                    opOR("to")->opEQ($id)->order_by("time")->desc()->exe();
                if (count($transactions) > 0) return $transactions;
            }
        }
        return false;
    }
    private function _LoadCalls($arguments)
    {
        return false;
    }
    private function _FillUpBalance($arguments)
    {
        return false;
    }
    private function _ChangeUserData($arguments)
    {
        syslog(LOG_INFO, "OXC: ChangeUserData");
        $user = SDB::select()->from("users")->where("phone")->opEQ($arguments[0])->exe();
        if (count($user) > 0)
        {
            $user = $user[0];
            $id = $user["id"];
            $token = $arguments[1];
            if (SDB::select()->from("auth")->where("user")->opEQ($id)->opAND("token")->opEQ
                ($token)->count() > 0)
            {
                $new_data = $arguments[2];
                SDB::update("users")->set(array(
                    "last_name" => $new_data["last-name"],
                    "name" => $new_data["first-name"],
                    "second_name" => $new_data["second-name"],
                    "email" => $new_data["email"],
                    "phone" => $new_data["phone"]))->where("id")->opEQ($id)->exe();
                return true;
            }
        }
        return false;
    }
    private function _SearchOffers($arguments)
    {
        syslog(LOG_INFO, "OPXC: _SearchOffers");
        $param = $arguments[2];
        $cat = $param["category"];
        $loc = $param["location"];
        if ($this->RequestIsAuthorized($arguments[0], $arguments[1], "partners"))
        {
            $result = array();
            $prep = SDB::select()->from("dongles")->where("leaser")->opEQ(0);
            if ($cat != 0) $prep->opAND("catid")->opEQ($cat);
            if ($loc != 0) $prep->opAND("locid")->opEQ($loc);
            $result["count"] = $prep->count();
            return $result;
        }
        return false;
    }
    private function _RequestOffer($arguments)
    {
        syslog(LOG_INFO, "OPXC: _RequestOffer");
        $param = $arguments[2];
        $cat = $param["category"];
        $loc = $param["location"];
        if ($pid = $this->RequestIsAuthorized($arguments[0], $arguments[1], "partners"))
        {
            SDB::insert()->into("offer_requests")->values(array(
                "partner" => $pid,
                "category" => $cat,
                "location" => $loc,
                "status" => "created",
                "created" => time()))->exe();
        }
        return false;
    }
    private function _LoadRequestedOffers($arguments)
    {
        syslog(LOG_INFO, "OPXC: _LoadRequestedOffers");
        if ($pid = $this->RequestIsAuthorized($arguments[0], $arguments[1], "partners"))
        {
            $result = SDB::select()->from("offer_requests")->where("partner")->opEQ($pid)->
                exe();
            if (count($result) > 0) return $result;
        }
        return false;
    }
    public function _LoadStatistics($arguments)
    {
        syslog(LOG_INFO, "OPXC: _LoadStatistics");
        if ($pid = $this->RequestIsAuthorized($arguments[0], $arguments[1], "partners"))
        {
            $stats = SDB::select(array(
                "id" => "routes.id",
                "time" => "routes.created",
                "number" => "dongles.phone",
                "status" => "routes.state",
                "payin" => "transactions.amount"))->from("routes")->inner_join("dongles")->on(array
                ("routes.client_dongle" => "dongles.id"))->inner_join("transactions")->on(array
                ("routes.hash" => "transactions.desc2"))->where("routes.generator")->opEQ($pid)->
                opAND("transactions.desc1")->opEQ("RG_PAYIN")->opAND("transactions.desc2")->
                opEQ("[routes.hash]")->opAND("transactions.pay_to")->opEQ($pid)->exe();
            if (count($stats) > 0) return $stats;
        }
        return false;
    }
}
