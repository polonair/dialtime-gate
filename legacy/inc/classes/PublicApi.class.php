<?php

class PublicApi extends CommonApi
{
    # Methods
    public static function Execute($data)
    {
        $method = $data["method"];
        syslog(LOG_INFO, "got method: $method");
        $parts = explode("::", $method);
        if (count($parts) === 2)
        {
            if ($parts[0] === "Partner") (new Public_PartnerApi())->ExecuteCommand($data);
            elseif ($parts[0] === "Client") (new Public_ClientApi())->ExecuteCommand($data);
        }
    }
}
