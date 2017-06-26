<?php

require_once ("/etc/belfry2/config.php");
require_once (__dir__ . "/classes/SwiftMailer/swift_required.php");

if (!defined("RUN_MODE")) exit(51);

if (defined("BELFRY2_ROLE"))
{
    req_classes("Processor", "CDB");
    switch (BELFRY2_ROLE)
    {
        case "GATE":
            req_classes("GateController", "GateMailer");
            switch (RUN_MODE)
            {
                case "AGI":
                    req_classes("Task", "Route", "Dongle", "GDB", "Caller", "Agi", "CallInfo",
                        "CallGateProcessor", "Call", "User", "DialResult");
                    break;
                case "CLI":
                    req_classes("Toucher", "Synchronizer", "CliGateProcessor", "Dongle", "Ping",
                        "GDB", "Command", "Croner");
                    break;
                case "WEB":
                    req_classes("HttpGateProcessor");
                    break;
            }
            break;
        case "SERVER":
            req_classes("ServerController", "Command", "SDB", "CommonApi");
            switch (RUN_MODE)
            {
                case "CLI":
                    req_classes("CliServerProcessor", "Synchronizer", "Toucher", "Tasker", "Patcher",
                        "Recorder", "LocalStore", "TaskPortion");
                    break;
                case "IWEB":
                    req_classes("InnerHttpServerProcessor", "Ping", "Toucher", "PrivateApi");
                    break;
                case "OWEB":
                    req_classes("OuterHttpServerProcessor", "Public_ClientApi", "Public_PartnerApi",
                        "PublicApi", "Smsc", "Mailer");
                    break;
            }
            break;
    }
}

function req_classes()
{
    $args = func_get_args();
    foreach ($args as $arg) req_class($arg);
}
function req_class($class)
{
    require_once (__dir__ . "/classes/$class.class.php");
}
