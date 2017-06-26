<?php

namespace Polonairs\Dialtime\GateBundle\Agi;

class AudioConversion
{
    private $inputFn = "";
    private $outputFn = "";

    public function __construct($wavFn)
    {
        $this->inputFn = $wavFn;
        $this->outputFn = str_replace(".wav", ".mp3", $this->inputFn);
    }
    public function execute()
    {
        $wav = $this->inputFn;
        $mp3 = $this->outputFn;
        exec("chmod -c 0777 $wav");
        if (file_exists($wav)) exec("lame -V 7 $wav $mp3");
        else exec("echo no-record >> $mp3");
        exec("chmod -c 0777 $mp3");
    }
    public function getConverted()
    {
        return $this->outputFn;
    }
    public function free()
    {
        $wav = $this->inputFn;
        $mp3 = $this->outputFn;
        exec("rm -rf $wav");
        exec("rm -rf $mp3");
    }
}
