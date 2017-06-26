<?php

ini_set('error_reporting', E_ALL);
//ini_set('display_errors', 1);
//ini_set('display_startup_errors', 1);
ini_set('error_log', 'syslog');

openlog("Belfry2", LOG_PID | LOG_PERROR, LOG_LOCAL0);

require (__dir__ . "/autoload.php");

if (defined("BELFRY2_ROLE"))
{
    syslog(LOG_INFO, "belfry2 logging started");
    switch (BELFRY2_ROLE)
    {
        case "GATE":
            GateController::Execute();
            break;
        case "SERVER":
            ServerController::Execute();
            break;
    }
}
else
{
    syslog(LOG_INFO, "belfry2 disabled");
}

closelog();

exit;
