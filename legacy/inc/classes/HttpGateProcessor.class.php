<?php

class HttpGateProcessor extends Processor
{
    # Methods
    public function Process()
    {
        system(BELFRY2_MAINDIR . "/cli/main.cli sync &");
    }
}
