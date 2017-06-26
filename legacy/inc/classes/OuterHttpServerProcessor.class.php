<?php

class OuterHttpServerProcessor extends Processor
{
    # Methods
    public function Process()
    {
        syslog(LOG_INFO, "server -> OUTER branch");
        $command = Command::Load();
        switch ($command->getCommand())
        {
            case Command::COM_PUBLIC_EXEC:
                return PublicApi::Execute($command->getData());
        }
    }
}
