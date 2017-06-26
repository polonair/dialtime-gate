<?php

class CliServerProcessor extends Processor
{
    # Methods
    public function Process()
    {
        syslog(LOG_INFO, "server -> cli branch");
        global $argc, $argv;
        if ($argc > 1)
        {
            switch ($argv[1])
            {
                case "sync":
                    if ($argc > 3) Synchronizer::Synchronize($argv[2], $argv[3]);
                    break;
                case "touch":
                    if ($argc > 2) Toucher::Touch($argv[2]);
                    break;
            }
        }
        else
        {
            syslog(LOG_INFO, "server -> cli -> cron branch");
            Tasker::UpdateTasks();
            Patcher::Patch();
            Recorder::UpdateRecords();
        }
    }
}
