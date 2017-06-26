<?php

class CliGateProcessor extends Processor
{
    # Methods
    public function Process()
    {
        syslog(LOG_INFO, "gate -> cli branch");
        Dongle::UpdateDongles();
        global $argc, $argv;
        syslog(LOG_INFO, "got arguments: '$argc', '" . json_encode($argv) . "'");
        if ($argc == 2)
        {
            switch ($argv[1])
            {
                case "sync":
                    return Synchronizer::Synchronize();
                case "touch":
                    return Toucher::Touch();
                default:
                    syslog(LOG_WARNING, " invalid argument: '$argc', '" . $argv[1] .
                        "'; will croning");
                    return Croner::DoCron();
            }
        }
        else  return Croner::DoCron();
    }
}
