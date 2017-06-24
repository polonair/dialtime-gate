<?php

namespace Polonairs\Dialtime\GateBundle\Agi;

use Polonairs\Dialtime\GateBundle\Entity\Call;
use Polonairs\Dialtime\GateBundle\Entity\Route;

//SELECT MOD(`id`, 5) as `d`, MD5(GROUP_CONCAT(`hash` SEPARATOR '-')) as `hash` FROM `calls` GROUP BY `d`

class Agi
{
    const AGIRES_OK = 200;

	private $in = null;
	private $out = null;
	private $request = [];
	private $origination = null;
    private $le = "\n";

	public function __construct($le) { $this->le = $le; }
	public function init()
	{
        ob_implicit_flush(true);
        $this->in = defined('STDIN') ? STDIN : fopen('php://stdin', 'r');
        $this->out = defined('STDOUT') ? STDOUT : fopen('php://stdout', 'w');

        $str = fgets($this->in);
        //while ($str != "\n")
        //while ($str != "\r\n")
        while ($str != $this->le)
        {
        	$s = strpos($str, ':');
        	$key = substr($str, 0, $s);
        	$value = trim(substr($str, $s + 1));
            $this->request[$key] = $value;
            $str = fgets($this->in);
        }
        return $this;
	}
	public function getOrigination()
	{
		if ($this->origination === null)
		{
			$this->origination = new Origination($this->request);
		}
		return $this->origination;
	}

    public function selectTerminator(array $terminators)
    {
        // select terminator which is free at the moment
        return $terminators[0];
    }
    function evaluate($command)
    {
        $broken = ['code' => 500, 'result' => -1, 'data' => ''];

        if (!@fwrite($this->out, trim($command) . "\n")) return $broken;
        fflush($this->out);

        $count = 0;
        do { $str = trim(fgets($this->in, 4096)); }
        while ($str == '' && $count++ < 5);

        if ($count >= 5) return $broken;

        // parse result
        $ret['code'] = substr($str, 0, 3);
        $str = trim(substr($str, 3));

        if ($str{0} == '-') // we have a multiline response!
        {
            $count = 0;
            $str = substr($str, 1) . "\n";
            $line = fgets($this->in, 4096);
            while (substr($line, 0, 3) != $ret['code'] && $count < 5)
            {
                $str .= $line;
                $line = fgets($this->in, 4096);
                $count = (trim($line) == '') ? $count + 1 : 0;
            }
            if ($count >= 5) return $broken;
        }

        $ret['result'] = null;
        $ret['data'] = '';
        if ($ret['code'] != self::AGIRES_OK) $ret['data'] = $str;
        else // normal AGIRES_OK response
        {
            $parse = explode(' ', trim($str));
            $in_token = false;
            foreach ($parse as $token)
            {
                if ($in_token) // we previously hit a token starting with ')' but not ending in ')'
                {
                    $ret['data'] .= ' ' . trim($token, '() ');
                    if ($token{strlen($token) - 1} == ')') $in_token = false;
                }
                elseif ($token{0} == '(')
                {
                    if ($token{strlen($token) - 1} != ')') $in_token = true;
                    $ret['data'] .= ' ' . trim($token, '() ');
                }
                elseif (strpos($token, '='))
                {
                    $token = explode('=', $token);
                    $ret[$token[0]] = $token[1];
                }
                elseif ($token != '') $ret['data'] .= ' ' . $token;
            }
            $ret['data'] = trim($ret['data']);
        }
        return $ret;
    }
	public function exec($application, $options)
	{
        if (is_array($options)) $options = join('|', $options);
        return $this->evaluate("EXEC $application $options");
	}
	public function exec_dial($type, $identifier, $timeout = null, $options = null, $url = null)
	{
        $str = sprintf("%s/%s,%s,%s,%s", $type, $identifier, $timeout, $options, $url);
        return $this->exec('Dial', trim($str, ","));
	}
    function get_variable($variable, $getvalue = false)
    {
        $res = $this->evaluate("GET VARIABLE $variable");
        if ($getvalue == false) return ($res);
        return ($res['data']);
    }
	public function noop($data)
	{
		return $this->exec("NoOp", $data);
	}
    public function hangup($channel = '')
    {
        return $this->evaluate("HANGUP $channel");
    }
	public function dial_old(Call $call)
	{
        switch($call->getRoute()->getState())
        {
            case Route::STATE_ACTIVE:
                $filename = "/var/lib/dialtime/gate/records/call_" . $call->getHash() . ".wav";
                if ($call->getDirection() === Call::DIRECTION_MT)
                {
                    $this->exec('MixMonitor', $filename, "b");
                    $this->exec_dial("SIP/" . $call->getRoute()->getTerminator(), $call->getRoute()->getMaster());
                    $this->hangup();
                    $call->setResult($this->get_variable("DIALSTATUS", true));
                    $call->setDialLength($this->get_variable("DIALEDTIME", true));
                    $call->setAnswerLength($this->get_variable("ANSWEREDTIME", true));
                    $call->setRecord(null);
                }
                else if ($call->getDirection() === Call::DIRECTION_MO)
                {
                    $this->exec('MixMonitor', $filename, "b");
                    $this->exec_dial("SIP/" . $call->getRoute()->getOriginator(), $call->getRoute()->getCustomer());
                    $this->hangup();
                    $call->setResult($this->get_variable("DIALSTATUS", true));
                    $call->setDialLength($this->get_variable("DIALEDTIME", true));
                    $call->setAnswerLength($this->get_variable("ANSWEREDTIME", true));
                    $call->setRecord(null);
                }
                else
                {
                    $call->setResult(Call::RESULT_CANCEL);
                    $call->setDialLength(0);
                    $call->setAnswerLength(0);
                    $call->setRecord(null);
                }
                break;
            case Route::STATE_RG:
                $filename = "/var/lib/dialtime/gate/records/call_" . $call->getHash() . ".wav";
                $this->exec('MixMonitor', $filename, "b");
                $this->exec_dial("SIP/".$call->getRoute()->getTerminator(), $call->getRoute()->getMaster());
                $this->hangup();
                $call->setResult($this->get_variable("DIALSTATUS", true));
                $call->setDialLength($this->get_variable("DIALEDTIME", true));
                $call->setAnswerLength($this->get_variable("ANSWEREDTIME", true));
                $call->setRecord(null);
                if ($call->getResult() === Call::RESULT_ANSWER)
                {
                    $call->getRoute()->setState(Route::STATE_ACTIVE);
                }
                break;
            case Route::STATE_FORBIDDEN:
            case Route::STATE_FORBIDDEN_TASKS:
            case Route::STATE_NO_WAY:
            case Route::STATE_SPAM:
                $call->setResult(Call::RESULT_CANCEL);
                $call->setDialLength(0);
                $call->setAnswerLength(0);
                $call->setRecord(null);
                break;
            default:
                //echo "caught default in dial function!!\r\n";
        }
		//echo
        //dump($call);
	}
    public function dial(Call $call)
    {
        $filename = "/var/lib/dialtime/gate/records/call_" . $call->getHash() . ".wav";
        $route = $call->getRoute();
        if ((($route->getState() === Route::STATE_INACTIVE) && ($call->getDirection() === Call::DIRECTION_DR)) ||
            (($route->getState() === Route::STATE_ACTIVE) && ($call->getDirection() === Call::DIRECTION_MT)) ||
            (($route->getState() === Route::STATE_ACTIVE) && ($call->getDirection() === Call::DIRECTION_RG)))
        {
            $this->exec('MixMonitor', $filename, "b");
            $this->exec_dial("SIP/" . $call->getRoute()->getTerminator(), $call->getRoute()->getMaster());
            $this->hangup();
            $call->setResult($this->get_variable("DIALSTATUS", true));
            $call->setDialLength($this->get_variable("DIALEDTIME", true));
            $call->setAnswerLength($this->get_variable("ANSWEREDTIME", true));
            // lame business here
            $fstr = fopen($filename, 'rb');
            $call->setRecord(stream_get_contents($fstr));
            // remove files
            return;
        }
        elseif (($route->getState() === Route::STATE_ACTIVE) && ($call->getDirection() === Call::DIRECTION_MO))
        {
            $this->exec('MixMonitor', $filename, "b");
            $this->exec_dial("SIP/" . $call->getRoute()->getOriginator(), $call->getRoute()->getCustomer());
            $this->hangup();
            $call->setResult($this->get_variable("DIALSTATUS", true));
            $call->setDialLength($this->get_variable("DIALEDTIME", true));
            $call->setAnswerLength($this->get_variable("ANSWEREDTIME", true));
            // lame business here
            $fstr = fopen($filename, 'rb');
            $call->setRecord(stream_get_contents($fstr));
            // remove files
            return;
        }
        $call->setResult(Call::RESULT_CANCEL);
        $call->setDialLength(0);
        $call->setAnswerLength(0);
        $call->setRecord(null);
    }
}
