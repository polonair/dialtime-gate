<?php

class InnerHttpServerProcessor extends Processor
{
    # Methods
    public function Process()
    {
        syslog(LOG_INFO, "server -> INNER branch");
        $command = Command::Load();
        switch ($command->getCommand())
        {
            case Command::COM_PING:
                return Ping::ProcessPing($command->getData());
            case Command::COM_TOUCH:
                return Toucher::ProcessTouch($command->getData());
            case Command::COM_PRIVATE_EXEC:
                return PrivateApi::Execute($command->getData());
        }
    }
}
