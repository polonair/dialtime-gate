<?php

class GateController
{
    # Methods
    public static function Execute()
    {
        syslog(LOG_INFO, "gate branch");
        switch (RUN_MODE)
        {
            case "AGI":
                return (new CallGateProcessor())->Process();
            case "CLI":
                return (new CliGateProcessor())->Process();
            case "WEB":
                return (new HttpGateProcessor())->Process();
        }
    }
}
