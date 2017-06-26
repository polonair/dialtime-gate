<?php

class ServerController
{
    # Methods
    public static function Execute()
    {
        syslog(LOG_INFO, "server branch");
        switch (RUN_MODE)
        {
            case "CLI":
                return (new CliServerProcessor())->Process();
            case "IWEB":
                return (new InnerHttpServerProcessor())->Process();
            case "OWEB":
                return (new OuterHttpServerProcessor())->Process();
        }
    }
}
