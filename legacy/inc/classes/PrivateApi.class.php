<?php

class PrivateApi extends CommonApi
{
    # Methods
    public static function Execute($data)
    {
        (new PrivateApi())->ExecuteCommand($data);
    }
    public function ExecuteCommand($data)
    {
        syslog(LOG_INFO, "inner starts to execute command");
        switch ($data["method"])
        {
            case "Admin::LoadAttentions":
                $result = $this->LoadAttentions($data["arguments"], "admins");
                break;
            case "Admin::GetToken":
                $result = $this->GetToken($data["arguments"], "admins");
                break;
            case "Admin::IsTokenValid":
                $result = $this->IsTokenValid($data["arguments"], "admins");
                break;
            case "Admin::LoadDongles":
                $result = $this->_LoadDongles($data["arguments"]);
                break;
            case "Admin::LoadDongle":
                $result = $this->_LoadDongle($data["arguments"]);
                break;
            case "Admin::EditDongle":
                $result = $this->_EditDongle($data["arguments"]);
                break;
            case "Admin::EditCategory":
                $result = $this->_EditCategory($data["arguments"]);
                break;
            case "Admin::EditLocation":
                $result = $this->_EditLocation($data["arguments"]);
                break;
            case "Admin::LoadCategories":
                $result = $this->LoadCategories($data["arguments"], "admins");
                break;
            case "Admin::LoadLocations":
                $result = $this->LoadLocations($data["arguments"], "admins");
                break;
            case "Admin::CreateLocation":
                $result = $this->_CreateLocation($data["arguments"]);
                break;
            case "Admin::CreateCategory":
                $result = $this->_CreateCategory($data["arguments"]);
                break;
            case "Admin::LoadCategory":
                $result = $this->_LoadCategory($data["arguments"]);
                break;
            case "Admin::LoadLocation":
                $result = $this->_LoadLocation($data["arguments"]);
                break;
            case "Admin::LoadRequestedOffers":
                $result = $this->_LoadRequestedOffers($data["arguments"]);
                break;
            case "Admin::AcceptOffer":
                $result = $this->_AcceptOffer($data["arguments"]);
                break;
            case "Admin::DeclineOffer":
                $result = $this->_DeclineOffer($data["arguments"]);
                break;
        }
        $result = json_encode($result);
        syslog(LOG_INFO, "IXC result jsoned: $result");
        echo base64_encode($result);
    }
    private function _LoadDongles($arguments)
    {
        syslog(LOG_INFO, "IXC: _LoadDongles");
        if ($this->RequestIsAuthorized($arguments[0], $arguments[1], "admins"))
        {
            $dongles = SDB::select(array(
                "imsi" => "dongles.id",
                "gate" => "dongles.gate_id",
                "phone" => "dongles.phone",
                "catid" => "dongles.catid",
                "catname" => "categories.name",
                "locid" => "dongles.locid",
                "locname" => "locations.name",
                "updated" => "dongles.updated"))->from("dongles")->left_outer_join("categories")->
                on(array("dongles.catid" => "categories.id"))->left_outer_join("locations")->on(array
                ("dongles.locid" => "locations.id"))->exe();
            if (count($dongles) > 0) return $dongles;
        }
        return false;
    }
    private function _LoadDongle($arguments)
    {
        syslog(LOG_INFO, "IXC: _LoadDongle");
        $imsi = $arguments[2];
        if ($this->RequestIsAuthorized($arguments[0], $arguments[1], "admins"))
        {
            $dongles = SDB::select(array(
                "imsi" => "dongles.id",
                "gate" => "dongles.gate_id",
                "phone" => "dongles.phone",
                "catid" => "dongles.catid",
                "catname" => "categories.name",
                "locid" => "dongles.locid",
                "locname" => "locations.name",
                "updated" => "dongles.updated"))->from("dongles")->left_outer_join("categories")->
                on(array("dongles.catid" => "categories.id"))->left_outer_join("locations")->on(array
                ("dongles.locid" => "locations.id"))->where("dongles.id")->opEQ($imsi)->exe();
            if (count($dongles) > 0) return $dongles;
        }
        return false;
    }
    private function _EditDongle($arguments)
    {
        syslog(LOG_INFO, "IXC: _EditDongle");
        if ($this->RequestIsAuthorized($arguments[0], $arguments[1], "admins"))
        {
            $dongleData = $arguments[2];
            if (SDB::update("dongles")->set(array(
                "phone" => $dongleData["phone"],
                "catid" => $dongleData["category"],
                "locid" => $dongleData["location"]))->where("id")->opEQ($dongleData["name"])->
                exe()) return true;
        }
        return false;
    }
    private function _EditCategory($arguments)
    {
        syslog(LOG_INFO, "IXC: _EditCategory");
        if ($this->RequestIsAuthorized($arguments[0], $arguments[1], "admins"))
        {
            $data = $arguments[2];
            if (SDB::update("categories")->set(array(
                "name" => $data["name"],
                "description" => $data["description"],
                "root" => $data["root"]))->where("id")->opEQ($data["id"])->exe()) return true;
        }
        return false;
    }
    private function _EditLocation($arguments)
    {
        syslog(LOG_INFO, "IXC: _EditLocation");
        if ($this->RequestIsAuthorized($arguments[0], $arguments[1], "admins"))
        {
            $data = $arguments[2];
            if (SDB::update("locations")->set(array(
                "name" => $data["name"],
                "description" => $data["description"],
                "root" => $data["root"]))->where("id")->opEQ($data["id"])->exe()) return true;
        }
        return false;
    }
    private function _CreateLocation($arguments)
    {
        syslog(LOG_INFO, "IXC: _CreateLocation");
        $location = $arguments[2];
        if ($this->RequestIsAuthorized($arguments[0], $arguments[1], "admins"))
        {
            if (SDB::insert()->into("locations")->values(array(
                "name" => $location["name"],
                "description" => $location["description"],
                "root" => $location["parent"]))->exe()) return true;
        }
        return false;
    }
    private function _CreateCategory($arguments)
    {
        syslog(LOG_INFO, "IXC: _CreateCategory");
        $category = $arguments[2];
        if ($this->RequestIsAuthorized($arguments[0], $arguments[1], "admins"))
        {
            if (SDB::insert()->into("categories")->values(array(
                "name" => $category["name"],
                "description" => $category["description"],
                "root" => $category["parent"]))->exe()) return true;
        }
        return false;
    }
    private function _LoadCategory($arguments)
    {
        syslog(LOG_INFO, "IXC: _LoadCategory");
        $id = $arguments[2];
        if ($this->RequestIsAuthorized($arguments[0], $arguments[1], "admins"))
        {
            $categories = SDB::select(array(
                "id" => "categories1.id",
                "name" => "categories1.name",
                "description" => "categories1.description",
                "root_id" => "categories1.root",
                "root_name" => "categories2.name"))->from("categories")->as_("categories1")->
                left_outer_join("categories")->as_("categories2")->on(array("categories1.root" =>
                    "categories2.id"))->where("categories1.id")->opEQ($id)->exe();
            if (count($categories) > 0) return $categories;
        }
        return false;
    }
    private function _LoadLocation($arguments)
    {
        syslog(LOG_INFO, "IXC: _LoadLocation");
        $id = $arguments[2];
        if ($this->RequestIsAuthorized($arguments[0], $arguments[1], "admins"))
        {
            $locations = SDB::select(array(
                "id" => "locations1.id",
                "name" => "locations1.name",
                "description" => "locations1.description",
                "root_id" => "locations1.root",
                "root_name" => "locations2.name"))->from("locations")->as_("locations1")->
                left_outer_join("locations")->as_("locations2")->on(array("locations1.root" =>
                    "locations2.id"))->where("locations1.id")->opEQ($id)->exe();
            if (count($locations) > 0) return $locations;
        }
        return false;
    }
    private function _LoadRequestedOffers($arguments)
    {
        syslog(LOG_INFO, "OAXC: _LoadRequestedOffers");
        if ($pid = $this->RequestIsAuthorized($arguments[0], $arguments[1], "admins"))
        {
            $result = SDB::select()->from("offer_requests")->exe();
            if (count($result) > 0)
            {
                foreach ($result as $key => $offer)
                {
                    $numbers = SDB::select()->from("dongles")->where("leaser")->opEQ(0)->opAND("catid")->
                        opEQ($offer["category"])->opAND("locid")->opEQ($offer["location"])->exe();
                    $result[$key]["numbers"] = $numbers;
                }
                return $result;
            }
        }
        return false;
    }
    private function _AcceptOffer($arguments)
    {
        syslog(LOG_INFO, "OAXC: _AcceptOffer");
        if ($pid = $this->RequestIsAuthorized($arguments[0], $arguments[1], "admins"))
        {
            $offer_id = $arguments[2]["offer"];
            $dongle_id = $arguments[2]["dongle"];
            SDB::update("offer_requests")->set(array("status" => "accepted"))->where("id")->
                opEQ($offer_id)->tag_to()->update("dongles")->set(array("leaser" => $pid))->
                where("id")->opEQ($dongle_id)->exe();
        }
        return false;
    }
    private function _DeclineOffer($arguments)
    {
        syslog(LOG_INFO, "OAXC: _DeclineOffer");
        if ($pid = $this->RequestIsAuthorized($arguments[0], $arguments[1], "admins"))
        {
            $offer_id = $arguments[2];
            SDB::update("offer_requests")->set(array("status" => "declined"))->where("id")->
                opEQ($offer_id)->exe();
        }
        return false;
    }
}
