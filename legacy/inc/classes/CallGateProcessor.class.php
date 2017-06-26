<?php

declare (ticks = 1) ;
function sig_handler($signo)
{
    syslog(LOG_DEBUG, "handling signal #$signo");
}

class CallGateProcessor extends Processor
{
    # Methods
    public function __construct()
    {
        pcntl_signal(SIGHUP, "sig_handler");
        set_time_limit(0);
    }
    public function Process()
    {
        syslog(LOG_INFO, "gate -> agi branch");
        $callInfo = new CallInfo();
        if (CallInfo::IsSpam($callInfo) < 1)
        {
            $route = Route::Load($callInfo);
            if ($route === null) $this->_ProcessNewRoute($callInfo);
            else  $route->Play();
        }
        $callInfo->Kill();
		GateMailer::SendCallReport();
    }
    private function _ProcessNewRoute($callInfo)
    {
        if ($callInfo->getCaller()->isUser())
        {
            syslog(LOG_INFO, "caller is user -> HANGUP");
			GateMailer::SendCallReport();
            return;
        }
        syslog(LOG_INFO, "caller is NOT user -> try to load task");
        $task = Task::Load($callInfo->getIncomeDongle());
        while ($task !== null)
        {
            $routes = $task->CreateRoutes($callInfo);
            syslog(LOG_INFO, "!!!routes loaded");
            foreach ($routes as $route)
            {
                syslog(LOG_INFO, "!!!try playing route");
                $dialResult = $route->PlayFirst();
                if ($dialResult->statusAnswered())
                {
                    syslog(LOG_INFO, "!!!call ok");
                    if ($task->PermaRoutes == 'yes') $route->Save();
                    $callInfo->Kill();
					GateMailer::SendCallReport();
                    return;
                }
                elseif ($dialResult->statusDeclined())
                {
                    syslog(LOG_INFO, "!!!call declined");
                    $callInfo->Kill();
					GateMailer::SendCallReport();
                    return; //break;
                }
                elseif ($dialResult->statusBroke())
                {
                    syslog(LOG_INFO, "!!!call broken");
                    $callInfo->Kill();
					GateMailer::SendCallReport();
                    return; //break;
                }
                else
                {
                    syslog(LOG_INFO, "!!!call X3");
                    $callInfo->Kill();
					GateMailer::SendCallReport();
                    return; //
                }
            }
            $task = Task::Load($callInfo->getIncomeDongle());
        }
        $callInfo->Kill();
		GateMailer::SendCallReport();
    }
}
