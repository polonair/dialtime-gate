<?php

class Public_ClientApi extends CommonApi
{
    # Methods
    public function ExecuteCommand($data)
    {
        syslog(LOG_INFO, "outer starts to execute command");
        switch ($data["method"])
        {
            case "Client::LoadAttentions":
                $result = $this->LoadAttentions($data["arguments"], "clients");
                break;
            case "Client::GetToken":
                $result = $this->GetToken($data["arguments"], "clients");
                break;
            case "Client::Register":
                $result = $this->Register($data["arguments"], "clients");
                break;
            case "Client::IsTokenValid":
                $result = $this->IsTokenValid($data["arguments"], "clients");
                break;
            case "Client::ResetPassword":
                $result = $this->ResetPassword($data["arguments"], "clients");
                break;
            case "Client::LoadUser":
                $result = $this->_LoadUser($data["arguments"]);
                break;
            case "Client::ToggleFlag":
                $result = $this->_ToggleFlag($data["arguments"]);
                break;
            case "Client::LoadTransactions":
                $result = $this->_LoadTransactions($data["arguments"]);
                break;
            case "Client::LoadCalls":
                $result = $this->_LoadCalls($data["arguments"]);
                break;
            case "Client::FillUpBalance":
                $result = $this->_FillUpBalance($data["arguments"]);
                break;
            case "Client::ChangeUserData":
                $result = $this->_ChangeUserData($data["arguments"]);
                break;
            case "Client::LoadIndexPageData":
                $result = $this->_LoadIndexPageData($data["arguments"]);
                break;
            case "Client::LoadAccountPageData":
                $result = $this->_LoadAccountPageData($data["arguments"]);
                break;
            case "Client::LoadHistoryPageData":
                $result = $this->_LoadHistoryPageData($data["arguments"]);
                break;
            case "Client::LoadFinancesPageData":
                $result = $this->_LoadFinancesPageData($data["arguments"]);
                break;
            case "Client::SetAllFlags":
                $result = $this->_SetAllFlags($data["arguments"]);
                break;
            case "Client::SetOneFlags":
                $result = $this->_SetOneFlags($data["arguments"]);
                break;
            case "Client::RequestRecord":
                $result = $this->_RequestRecord($data["arguments"]);
                break;
            case "Client::LoadAddAbilityData":
                $result = $this->_LoadAddAbilityData($data["arguments"]);
                break;
            case "Client::AddAbility":
                $result = $this->_AddAbility($data["arguments"]);
                break;
            case "Client::DeleteAbility":
                $result = $this->_DeleteAbility($data["arguments"]);
                break;
            case "Client::ProlongRoute":
                $result = $this->_ProlongRoute($data["arguments"]);
                break;
            case "Client::RemoveRoute":
                $result = $this->_RemoveRoute($data["arguments"]);
                break;
            case "Client::AbuseRoute":
                $result = $this->_AbuseRoute($data["arguments"]);
                break;
            case "Client::ProcessPayCode":
                $result = $this->_ProcessPayCode($data["arguments"]);
                break;
            case "Client::SendSupportMessage":
                $result = $this->_SendSupportMessage($data["arguments"]);
                break;
            case "Client::RequestFillUp":
                $result = $this->_RequestFillUp($data["arguments"]);
                break;
        }
        $result = json_encode($result);
        syslog(LOG_INFO, "OXC result jsoned: $result");
        echo base64_encode($result);
    }
    private function _LoadUser($arguments)
    {
        syslog(LOG_INFO, "OXC: LoadUser");
        $user = SDB::select(array(
            "id" => "users.id",
            "rq_state" => "clients.rq_state",
            "phone" => "users.phone",
            "name" => "users.name",
            "second_name" => "users.second_name",
            "last_name" => "users.last_name",
            "account" => "clients.balance",
            "email" => "users.email"))->from("users")->inner_join("clients")->on(array("users.id" =>
                "clients.id"))->where("phone")->opEQ($arguments[0])->exe();
        if (count($user) > 0)
        {
            $user = $user[0];
            $id = $user["id"];
            $token = $arguments[1];
            if (SDB::select()->from("auth")->where("user")->opEQ($id)->opAND("token")->opEQ
                ($token)->count() > 0)
            {
                return array(
                    "id" => $user["id"],
                    "phone" => $user["phone"],
                    "status" => $user['rq_state'],
                    "name" => $user['name'],
                    "second_name" => $user['second_name'],
                    "last_name" => $user['last_name'],
                    "account" => $user['account'],
                    "email" => $user['email']);
            }
        }
        return false;
    }
    private function _ToggleFlag($arguments)
    {
        syslog(LOG_INFO, "OXC: ToggleFlag");
        $user = SDB::select(array("id" => "users.id", "rq_state" => "clients.rq_state"))->
            from("users")->inner_join("clients")->on(array("users.id" => "clients.id"))->
            where("phone")->opEQ($arguments[0])->exe();
        if (count($user) > 0)
        {
            $user = $user[0];
            $id = $user["id"];
            $token = $arguments[1];
            if (SDB::select()->from("auth")->where("user")->opEQ($id)->opAND("token")->opEQ
                ($token)->count() > 0)
            {
                $nstate = $user["rq_state"] == "up" ? "down" : "up";
                if (SDB::update("clients")->set(array("rq_state" => $nstate, "last_status_at" =>
                        time()))->where("id")->opEQ($id)->exe()) return true;
            }
        }
        return false;
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
        syslog(LOG_INFO, "OCXC: _ChangeUserData");
        if ($pid = $this->RequestIsAuthorized($arguments[0], $arguments[1], "clients"))
        {
            $result = SDB::func("change_client_data", array(
                $pid,
                $arguments[2]["last-name"],
                $arguments[2]["first-name"],
                $arguments[2]["second-name"],
                $arguments[2]["email"],
                //$arguments[2]["phone"],
                $arguments[2]["gc_from_time"],
                $arguments[2]["gc_to_time"],
                $arguments[2]["gc_from_dow"],
                $arguments[2]["gc_to_dow"],
                $arguments[2]["timezone"]))->exe();
            return true;
        }
        return false;
    }
    private function _LoadIndexPageData($arguments)
    {
        syslog(LOG_INFO, "OCXC: _LoadIndexPageData");
        if ($pid = $this->RequestIsAuthorized($arguments[0], $arguments[1], "clients"))
        {
            $r1 = SDB::select(array(
                "first_name" => "users.name",
                "phone" => "users.phone",
                "balance" => "clients.balance",
                "accept_status" => "clients.accept_status",
                "get_calls_from" => "clients.call_time_from",
                "get_calls_to" => "clients.call_time_to",
                "decline_reason" => "clients.decline_reason")) #
                ->from("users") #
                ->inner_join("clients")->on(array("users.id" => "clients.id")) #
                ->where("users.id")->opEQ($pid)->exe()[0];
            $r2 = SDB::select("[count(*)]") #
                ->from("abilities") #
                ->where("state")->opEQ("up") #
                ->opAND("client")->opEQ($pid)->exe()[0]["count(*)"];
            $r3 = SDB::select(array(
                "id" => "abilities.id",
                "status" => "abilities.state",
                "location_name" => "locations.name",
                "category_name" => "categories.name")) #
                ->from("abilities") #
                ->inner_join("locations")->on(array("abilities.location" => "locations.id")) #
                ->inner_join("categories")->on(array("abilities.category" => "categories.id")) #
                ->where("client")->opEQ($pid)->exe();
            $r4 = SDB::select("[count(*)]") #
                ->from("tasks") #
                ->where("user_id")->opEQ($pid) #
                ->opAND("state")->opEQ("active")->exe()[0]["count(*)"];
            return array(
                "client_first_name" => $r1["first_name"],
                "client_phone" => $r1["phone"],
                "client_balance" => $r1["balance"],
                "client_accept_status" => $r1["accept_status"],
                "client_decline_reason" => $r1["decline_reason"],
                "client_summary_status" => $r2 > 0 ? "up" : "down",
                "client_get_calls_from" => $r1["get_calls_from"],
                "client_get_calls_to" => $r1["get_calls_to"],
                "client_fact_status" => $r4 > 0 ? "up" : "down",
                "client_abilities" => $r3);
        }
        return false;
    }
    private function _LoadAccountPageData($arguments)
    {
        syslog(LOG_INFO, "OCXC: _LoadAccountPageData");
        if ($pid = $this->RequestIsAuthorized($arguments[0], $arguments[1], "clients"))
        {
            $r1 = SDB::select(array(
                "first_name" => "users.name",
                "second_name" => "users.second_name",
                "last_name" => "users.last_name",
                "email" => "users.email",
                "phone" => "users.phone",
                "call_time_from" => "clients.call_time_from",
                "call_time_to" => "clients.call_time_to",
                "call_time_from_dow" => "clients.call_dow_from",
                "call_time_to_dow" => "clients.call_dow_to",
                "timezone" => "clients.timezone")) #
                ->from("users") #
                ->inner_join("clients")->on(array("users.id" => "clients.id")) #
                ->where("users.id")->opEQ($pid)->exe()[0];
            $r2 = SDB::select(array(
                "id" => "abilities.id",
                "cat_name" => "categories.name",
                "loc_name" => "locations.name")) #
                ->from("abilities") #
                ->inner_join("categories")->on(array("abilities.category" => "categories.id")) #
                ->inner_join("locations")->on(array("abilities.location" => "locations.id")) #
                ->where("abilities.client")->opEQ($pid)->exe();
            return array(
                "client_last_name" => $r1["last_name"],
                "client_first_name" => $r1["first_name"],
                "client_second_name" => $r1["second_name"],
                "client_email" => $r1["email"],
                "client_phone" => $r1["phone"],
                "client_calls_from" => $r1["call_time_from"],
                "client_calls_to" => $r1["call_time_to"],
                "client_calls_from_dow" => $r1["call_time_from_dow"],
                "client_calls_to_dow" => $r1["call_time_to_dow"],
                "client_timezone" => $r1["timezone"],
                "client_abilities" => $r2);
        }
        return false;
    }
    private function _LoadHistoryPageData($arguments)
    {
        syslog(LOG_INFO, "OCXC: _LoadHistoryPageData");
        if ($pid = $this->RequestIsAuthorized($arguments[0], $arguments[1], "clients"))
        {
            $r1 = SDB::select(array(
                "phone" => "dongles.phone",
                "created" => "routes.created",
                "expires" => "routes.expired",
                "cost" => "transactions.amount",
                "state" => "routes.state",
                "id" => "routes.id")) #
                ->from("routes") #
                ->inner_join("dongles")->on(array("routes.user_dongle" => "dongles.id")) #
                ->inner_join("calls")->on(array("routes.hash" => "calls.route")) #
                ->inner_join("transactions")->on(array("calls.hash" => "transactions.desc2")) #
                ->where("calls.direction")->opEQ("RG") #
                ->opAND("transactions.desc1")->opEQ("RG_PAYOUT") #
                ->opAND("routes.user_id")->opEQ($pid)->exe();
            $r2 = SDB::select(array(
                "id" => "calls.hash",
                "time" => "calls.created_on",
                "phone" => "dongles.phone",
                "direction" => "calls.direction",
                "length" => "calls.answ_length",
                "cost" => "transactions.amount")) #
                ->from("calls") #
                ->inner_join("routes")->on(array("calls.route" => "routes.hash")) #
                ->inner_join("dongles")->on(array("routes.user_dongle" => "dongles.id")) #
                ->inner_join("transactions")->on(array("calls.hash" => "transactions.desc2")) #
                ->where("routes.user_id")->opEQ($pid)->exe();
            $r3 = SDB::select("timezone") #
                ->from("clients") #
                ->where("id")->opEQ($pid)->exe()[0]["timezone"];
            return array(
                "calls" => $r2,
                "routes" => $r1,
                "timezone" => $r3);
        }
        return false;
    }
    private function _LoadFinancesPageData($arguments)
    {
        syslog(LOG_INFO, "OCXC: _LoadFinancesPageData");
        if ($pid = $this->RequestIsAuthorized($arguments[0], $arguments[1], "clients"))
        {
            $balance = SDB::select(array("balance" => "clients.balance")) #
                ->from("clients") #
                ->where("clients.id")->opEQ($pid)->exe()[0]["balance"];
            $operations = SDB::select(array(
                "id" => "transactions.id",
                "to" => "transactions.pay_to",
                "from" => "transactions.pay_from",
                "time" => "transactions.time",
                "amount" => "transactions.amount",
                "desc1" => "transactions.desc1",
                "desc2" => "transactions.desc2",
                "desc3" => "transactions.desc3")) #
                ->from("transactions") #
                ->where("pay_from")->opEQ($pid) #
                ->opOR("pay_to")->opEQ($pid)->exe();
            $r3 = SDB::select("timezone") #
                ->from("clients") #
                ->where("id")->opEQ($pid)->exe()[0]["timezone"];
            return array(
                "balance" => $balance,
                "operations" => $operations,
                "timezone" => $r3);
        }
        return false;
    }
    private function _SetAllFlags($arguments)
    {
        syslog(LOG_INFO, "OCXC: _SetAllFlags");
        if ($pid = $this->RequestIsAuthorized($arguments[0], $arguments[1], "clients"))
        {
            SDB::exe_raw("CALL toggle_all_flags('$pid');");
            return true;
        }
        return false;
    }
    private function _SetOneFlags($arguments)
    {
        syslog(LOG_INFO, "OCXC: _SetOneFlags");
        if ($pid = $this->RequestIsAuthorized($arguments[0], $arguments[1], "clients"))
        {
            $abi = $arguments[2];
            SDB::exe_raw("CALL toggle_one_flags('$pid', '$abi');");
            return true;
        }
        return false;
    }
    private function _RequestRecord($arguments)
    {
        syslog(LOG_INFO, "OCXC: _RequestRecord");
        if ($pid = $this->RequestIsAuthorized($arguments[0], $arguments[1], "clients"))
        {
            $data = SDB::select(array(
                "hash" => "calls.hash",
                "created" => "calls.created_on",
                "phone" => "dongles.phone",
                "name" => "users.name",
                "email" => "users.email")) #
                ->from("calls") #
                ->inner_join("routes")->on(array("calls.route" => "routes.hash")) #
                ->inner_join("users")->on(array("routes.user_id" => "users.id")) #
                ->inner_join("dongles")->on(array("routes.user_dongle" => "dongles.id")) #
                ->where("calls.hash")->opEQ($arguments[2])->exe();
            $r3 = SDB::select("timezone") #
                ->from("clients") #
                ->where("id")->opEQ($pid)->exe()[0]["timezone"];
            if (count($data) > 0)
            {
                $data = $data[0];
                $result = Mailer::SendRecord($data["hash"], $data["created"] + $r3, $data["phone"],
                    $data["name"], $data["email"]);
                if ($result == "OK") return array("result" => "OK", "additional" => $data);
                if ($result == "FILE_NOT_FOUND") return array("result" => "BROKEN", "additional" => false);
            }
            return array("result" => "BROKEN", "additional" => false);
        }
        return false;
    }
    private function _LoadAddAbilityData($arguments)
    {
        syslog(LOG_INFO, "OCXC: _LoadAddAbilityData");
        if ($pid = $this->RequestIsAuthorized($arguments[0], $arguments[1], "clients"))
        {
            $locations = $this->LoadLocations($arguments, "clients");
            $categories = $this->LoadCategories($arguments, "clients");
            return array("locations" => $locations, "categories" => $categories);
        }
        return false;
    }
    private function _AddAbility($arguments)
    {
        syslog(LOG_INFO, "OCXC: _AddAbility");
        if ($pid = $this->RequestIsAuthorized($arguments[0], $arguments[1], "clients"))
        {
            $location = $arguments[2]["location"];
            $category = $arguments[2]["category"];
            SDB::insert()->into("abilities")->values(array(
                "client" => $pid,
                "location" => $location,
                "category" => $category,
                "state" => "down",
                "touch" => time()))->exe();
            return true;
        }
        return false;
    }
    private function _DeleteAbility($arguments)
    {
        syslog(LOG_INFO, "OCXC: _DeleteAbility");
        if ($pid = $this->RequestIsAuthorized($arguments[0], $arguments[1], "clients"))
        {
            $abi = $arguments[2];
            SDB::delete() #
                ->from("abilities") #
                ->where("id")->opEQ($abi) #
                ->opAND("client")->opEQ($pid)->exe();
            return true;
        }
        return false;
    }
    private function _ProlongRoute($arguments)
    {
        syslog(LOG_INFO, "OCXC: _ProlongRoute");
        if ($pid = $this->RequestIsAuthorized($arguments[0], $arguments[1], "clients"))
        {
            $route = $arguments[2];
            $r1 = SDB::select()->from("routes")->where("id")->opEQ($route)->exe();
            if (count($r1) > 0)
            {
                SDB::update("routes") #
                    ->set(array("expired" => $r1[0]["expired"] + 60 * 60 * 24 * 7)) #
                    ->where("id")->opEQ($route) #
                    ->opAND("user_id")->opEQ($pid)->exe();
            }
            return true;
        }
        return false;
    }
    private function _RemoveRoute($arguments)
    {
        syslog(LOG_INFO, "OCXC: _RemoveRoute");
        if ($pid = $this->RequestIsAuthorized($arguments[0], $arguments[1], "clients"))
        {
            $route = $arguments[2];
            SDB::update("routes") #
                ->set(array("state" => "removed")) #
                ->where("id")->opEQ($route) #
                ->opAND("user_id")->opEQ($pid)->exe();
            return true;
        }
        return false;
    }
    private function _AbuseRoute($arguments)
    {
        syslog(LOG_INFO, "OCXC: _AbuseRoute");
        if ($pid = $this->RequestIsAuthorized($arguments[0], $arguments[1], "clients"))
        {
            $route = $arguments[2];
            SDB::update("routes") #
                ->set(array("state" => "abused")) #
                ->where("id")->opEQ($route) #
                ->opAND("user_id")->opEQ($pid)->exe();
            return true;
        }
        return false;
    }
    private function _ProcessPayCode($arguments)
    {
        syslog(LOG_INFO, "OCXC: _ProcessPayCode");

        $code = $arguments[0];
        $data = $arguments[1];
        if ($code === "sprypay")
        {
            $amount = $data["spAmount"];
            $payid = $data["spShopPaymentId"];
            $result = SDB::func("finallize_fillup_transaction", array($payid, $amount))->
                exe()[0]["result"];
            return $result;
        }
        else
        {
            return "error";
        }
    }
    private function _SendSupportMessage($arguments)
    {
        syslog(LOG_INFO, "OCXC: _SendSupportMessage");
        if (count($arguments) == 4)
        {
            if ($pid = $this->RequestIsAuthorized($arguments[2], $arguments[3], "clients")) 
                    Mailer::SendSupportFromUser($arguments[0], $arguments[1], $arguments[2]);
            else  return "wrong-credentials";
        }
        elseif (count($arguments) == 3) Mailer::SendSupportFromAnonim($arguments[0], $arguments[1],
                $arguments[2]);
        else  return "error";
        return "ok";
    }
    private function _RequestFillUp($args)
    {
        syslog(LOG_INFO, "OCXC: _RequestFillUp");
        if ($pid = $this->RequestIsAuthorized($args[0], $args[1], "clients"))
        {
            $amount = $args[2]["amount"];
            SDB::exe_raw("CALL start_fillup_transaction('$pid', '$amount', @pc, @em);");
            $result = SDB::exe_raw("SELECT @pc, @em;");
            if ($row = $result->fetch_assoc())
            {
                $paycode = $row["@pc"];
                $email = $row["@em"];
                $result = array();
                $result["result"] = "ok";
                $result["pay-url"] = "http://sprypay.ru/sppi/?" . #
                    "spShopId=" . urlencode("221204") . #
                    "&spShopPaymentId=" . urlencode($paycode) . #
                    "&spAmount=" . urlencode($amount) . #
                    "&spCurrency=" . urlencode("rur") . #
                    "&spPurpose=" . urlencode("Пополнение счета в сервисе Target-Call") . #
                    "&spUserEmail=" . urlencode($email) . #
                    "&lang=" . urlencode("ru");
                return $result;
            }
        }
        return false;
    }
}
