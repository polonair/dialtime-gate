<?php

class Tasker
{
    # Methods
    public static function UpdateTasks()
    {
        $min = floor(time() / 60);
        if ($min % BELFRY2_UPTASKS_PERIOD === 0)
        {
            syslog(LOG_INFO, "updating tasks");
            SDB::exe_raw("CALL update_tasks();");
            SDB::exe_raw("CALL update_service_tasks();");
        }
    }
}
