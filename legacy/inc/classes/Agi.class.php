<?php

define('AST_CONFIG_DIR', '/etc/asterisk/');
define('AST_SPOOL_DIR', '/var/spool/asterisk/');
define('AST_TMP_DIR', AST_SPOOL_DIR . '/tmp/');
define('DEFAULT_PHPAGI_CONFIG', AST_CONFIG_DIR . '/phpagi.conf');

define('AST_DIGIT_ANY', '0123456789#*');

define('AGIRES_OK', 200);

define('AST_STATE_DOWN', 0);
define('AST_STATE_RESERVED', 1);
define('AST_STATE_OFFHOOK', 2);
define('AST_STATE_DIALING', 3);
define('AST_STATE_RING', 4);
define('AST_STATE_RINGING', 5);
define('AST_STATE_UP', 6);
define('AST_STATE_BUSY', 7);
define('AST_STATE_DIALING_OFFHOOK', 8);
define('AST_STATE_PRERING', 9);

define('AUDIO_FILENO', 3); // STDERR_FILENO + 1

class Agi
{
    var $request;
    var $config;
    var $asmanager;
    var $in = null;
    var $out = null;
    var $audio = null;
    public $option_delim = ",";

    function __construct($config = null, $optconfig = array())
    {
        // load config
        if (!is_null($config) && file_exists($config)) $this->config = parse_ini_file($config, true);
        elseif (file_exists(DEFAULT_PHPAGI_CONFIG)) $this->config = parse_ini_file(DEFAULT_PHPAGI_CONFIG, true);

        // If optconfig is specified, stuff vals and vars into 'phpagi' config array.
        foreach ($optconfig as $var => $val) $this->config['phpagi'][$var] = $val;

        // add default values to config for uninitialized values
        if (!isset($this->config['phpagi']['error_handler'])) $this->config['phpagi']['error_handler'] = true;
        if (!isset($this->config['phpagi']['debug'])) $this->config['phpagi']['debug'] = false;
        if (!isset($this->config['phpagi']['admin'])) $this->config['phpagi']['admin'] = null;
        if (!isset($this->config['phpagi']['tempdir'])) $this->config['phpagi']['tempdir'] =
                AST_TMP_DIR;

        // festival TTS config
        if (!isset($this->config['festival']['text2wave'])) $this->config['festival']['text2wave'] =
                $this->which('text2wave');

        // swift TTS config
        if (!isset($this->config['cepstral']['swift'])) $this->config['cepstral']['swift'] =
                $this->which('swift');

        ob_implicit_flush(true);

        // open stdin & stdout
        $this->in = defined('STDIN') ? STDIN : fopen('php://stdin', 'r');
        $this->out = defined('STDOUT') ? STDOUT : fopen('php://stdout', 'w');

        // initialize error handler
        if ($this->config['phpagi']['error_handler'] == true)
        {
            set_error_handler('phpagi_error_handler');
            global $phpagi_error_handler_email;
            $phpagi_error_handler_email = $this->config['phpagi']['admin'];
            error_reporting(E_ALL);
        }

        // make sure temp folder exists
        $this->make_folder($this->config['phpagi']['tempdir']);

        // read the request
        $str = fgets($this->in);
        while ($str != "\n")
        {
            $this->request[substr($str, 0, strpos($str, ':'))] = trim(substr($str, strpos($str,
                ':') + 1));
            $str = fgets($this->in);
        }

        // open audio if eagi detected
        if ($this->request['agi_enhanced'] == '1.0')
        {
            if (file_exists('/proc/' . getmypid() . '/fd/3')) $this->audio = fopen('/proc/' .
                    getmypid() . '/fd/3', 'r');
            elseif (file_exists('/dev/fd/3'))
            {
                // may need to mount fdescfs
                $this->audio = fopen('/dev/fd/3', 'r');
            }
            else  $this->conlog('Unable to open audio stream');

            if ($this->audio) stream_set_blocking($this->audio, 0);
        }

        $this->conlog('AGI Request:');
        $this->conlog(print_r($this->request, true));
        $this->conlog('PHPAGI internal configuration:');
        $this->conlog(print_r($this->config, true));
    }
    function answer()
    {
        return $this->evaluate('ANSWER');
    }
    function channel_status($channel = '')
    {
        $ret = $this->evaluate("CHANNEL STATUS $channel");
        switch ($ret['result'])
        {
            case - 1:
                $ret['data'] = trim("There is no channel that matches $channel");
                break;
            case AST_STATE_DOWN:
                $ret['data'] = 'Channel is down and available';
                break;
            case AST_STATE_RESERVED:
                $ret['data'] = 'Channel is down, but reserved';
                break;
            case AST_STATE_OFFHOOK:
                $ret['data'] = 'Channel is off hook';
                break;
            case AST_STATE_DIALING:
                $ret['data'] = 'Digits (or equivalent) have been dialed';
                break;
            case AST_STATE_RING:
                $ret['data'] = 'Line is ringing';
                break;
            case AST_STATE_RINGING:
                $ret['data'] = 'Remote end is ringing';
                break;
            case AST_STATE_UP:
                $ret['data'] = 'Line is up';
                break;
            case AST_STATE_BUSY:
                $ret['data'] = 'Line is busy';
                break;
            case AST_STATE_DIALING_OFFHOOK:
                $ret['data'] = 'Digits (or equivalent) have been dialed while offhook';
                break;
            case AST_STATE_PRERING:
                $ret['data'] = 'Channel has detected an incoming call and is waiting for ring';
                break;
            default:
                $ret['data'] = "Unknown ({$ret['result']})";
                break;
        }
        return $ret;
    }
    function database_del($family, $key)
    {
        return $this->evaluate("DATABASE DEL \"$family\" \"$key\"");
    }
    function database_deltree($family, $keytree = '')
    {
        $cmd = "DATABASE DELTREE \"$family\"";
        if ($keytree != '') $cmd .= " \"$keytree\"";
        return $this->evaluate($cmd);
    }
    function database_get($family, $key)
    {
        return $this->evaluate("DATABASE GET \"$family\" \"$key\"");
    }
    function database_put($family, $key, $value)
    {
        $value = str_replace("\n", '\n', addslashes($value));
        return $this->evaluate("DATABASE PUT \"$family\" \"$key\" \"$value\"");
    }
    function set_global_var($pVariable, $pValue)
    {
        if (is_numeric($pValue)) return $this->evaluate("Set({$pVariable}={$pValue},g);");
        else  return $this->evaluate("Set({$pVariable}=\"{$pValue}\",g);");
    }
    function set_var($pVariable, $pValue)
    {
        if (is_numeric($pValue)) return $this->evaluate("Set({$pVariable}={$pValue});");
        else  return $this->evaluate("Set({$pVariable}=\"{$pValue}\");");
    }
    function exec($application, $options)
    {
        if (is_array($options)) $options = join('|', $options);
        return $this->evaluate("EXEC $application $options");
    }
    function get_data($filename, $timeout = null, $max_digits = null)
    {
        return $this->evaluate(rtrim("GET DATA $filename $timeout $max_digits"));
    }
    function get_variable($variable, $getvalue = false)
    {
        $res = $this->evaluate("GET VARIABLE $variable");

        if ($getvalue == false) return ($res);

        return ($res['data']);
    }
    function get_fullvariable($variable, $channel = false, $getvalue = false)
    {
        if ($channel == false)
        {
            $req = $variable;
        }
        else
        {
            $req = $variable . ' ' . $channel;
        }

        $res = $this->evaluate('GET VARIABLE FULL ' . $req);

        if ($getvalue == false) return ($res);

        return ($res['data']);

    }
    function hangup($channel = '')
    {
        return $this->evaluate("HANGUP $channel");
    }
    function noop($string = "")
    {
        return $this->evaluate("NOOP \"$string\"");
    }
    function receive_char($timeout = -1)
    {
        return $this->evaluate("RECEIVE CHAR $timeout");
    }
    function record_file($file, $format, $escape_digits = '', $timeout = -1, $offset = null,
        $beep = false, $silence = null)
    {
        $cmd = trim("RECORD FILE $file $format \"$escape_digits\" $timeout $offset");
        if ($beep) $cmd .= ' BEEP';
        if (!is_null($silence)) $cmd .= " s=$silence";
        return $this->evaluate($cmd);
    }
    function say_digits($digits, $escape_digits = '')
    {
        return $this->evaluate("SAY DIGITS $digits \"$escape_digits\"");
    }
    function say_number($number, $escape_digits = '')
    {
        return $this->evaluate("SAY NUMBER $number \"$escape_digits\"");
    }
    function say_phonetic($text, $escape_digits = '')
    {
        return $this->evaluate("SAY PHONETIC $text \"$escape_digits\"");
    }
    function say_time($time = null, $escape_digits = '')
    {
        if (is_null($time)) $time = time();
        return $this->evaluate("SAY TIME $time \"$escape_digits\"");
    }
    function send_image($image)
    {
        return $this->evaluate("SEND IMAGE $image");
    }
    function send_text($text)
    {
        return $this->evaluate("SEND TEXT \"$text\"");
    }
    function set_autohangup($time = 0)
    {
        return $this->evaluate("SET AUTOHANGUP $time");
    }
    function set_callerid($cid)
    {
        return $this->evaluate("SET CALLERID $cid");
    }
    function set_context($context)
    {
        return $this->evaluate("SET CONTEXT $context");
    }
    function set_extension($extension)
    {
        return $this->evaluate("SET EXTENSION $extension");
    }
    function set_music($enabled = true, $class = '')
    {
        $enabled = ($enabled) ? 'ON' : 'OFF';
        return $this->evaluate("SET MUSIC $enabled $class");
    }
    function set_priority($priority)
    {
        return $this->evaluate("SET PRIORITY $priority");
    }
    function set_variable($variable, $value)
    {
        $value = str_replace("\n", '\n', addslashes($value));
        return $this->evaluate("SET VARIABLE $variable \"$value\"");
    }
    function stream_file($filename, $escape_digits = '', $offset = 0)
    {
        return $this->evaluate("STREAM FILE $filename \"$escape_digits\" $offset");
    }
    function tdd_mode($setting)
    {
        return $this->evaluate("TDD MODE $setting");
    }
    function verbose($message, $level = 1)
    {
        foreach (explode("\n", str_replace("\r\n", "\n", print_r($message, true))) as $msg)
        {
            @syslog(LOG_WARNING, $msg);
            $ret = $this->evaluate("VERBOSE \"$msg\" $level");
        }
        return $ret;
    }
    function wait_for_digit($timeout = -1)
    {
        return $this->evaluate("WAIT FOR DIGIT $timeout");
    }
    function exec_absolutetimeout($seconds = 0)
    {
        return $this->exec('AbsoluteTimeout', $seconds);
    }
    function exec_agi($command, $args)
    {
        return $this->exec("AGI $command", $args);
    }
    function exec_setlanguage($language = 'en')
    {
        return $this->exec('Set', 'CHANNEL(language)=' . $language);
    }
    function exec_enumlookup($exten)
    {
        return $this->exec('EnumLookup', $exten);
    }
    function exec_dial($type, $identifier, $timeout = null, $options = null, $url = null)
    {
        return $this->exec('Dial', trim("$type/$identifier" . $this->option_delim . $timeout .
            $this->option_delim . $options . $this->option_delim . $url, $this->
            option_delim));
    }
    function exec_goto($a, $b = null, $c = null)
    {
        return $this->exec('Goto', trim($a . $this->option_delim . $b . $this->
            option_delim . $c, $this->option_delim));
    }
    function fastpass_say_digits(&$buffer, $digits, $escape_digits = '')
    {
        $proceed = false;
        if ($escape_digits != '' && $buffer != '')
        {
            if (!strpos(chr(255) . $escape_digits, $buffer{strlen($buffer) - 1})) $proceed = true;
        }
        if ($buffer == '' || $proceed)
        {
            $res = $this->say_digits($digits, $escape_digits);
            if ($res['code'] == AGIRES_OK && $res['result'] > 0) $buffer .= chr($res['result']);
            return $res;
        }
        return array('code' => AGIRES_OK, 'result' => ord($buffer{strlen($buffer) - 1}));
    }
    function fastpass_say_number(&$buffer, $number, $escape_digits = '')
    {
        $proceed = false;
        if ($escape_digits != '' && $buffer != '')
        {
            if (!strpos(chr(255) . $escape_digits, $buffer{strlen($buffer) - 1})) $proceed = true;
        }
        if ($buffer == '' || $proceed)
        {
            $res = $this->say_number($number, $escape_digits);
            if ($res['code'] == AGIRES_OK && $res['result'] > 0) $buffer .= chr($res['result']);
            return $res;
        }
        return array('code' => AGIRES_OK, 'result' => ord($buffer{strlen($buffer) - 1}));
    }
    function fastpass_say_phonetic(&$buffer, $text, $escape_digits = '')
    {
        $proceed = false;
        if ($escape_digits != '' && $buffer != '')
        {
            if (!strpos(chr(255) . $escape_digits, $buffer{strlen($buffer) - 1})) $proceed = true;
        }
        if ($buffer == '' || $proceed)
        {
            $res = $this->say_phonetic($text, $escape_digits);
            if ($res['code'] == AGIRES_OK && $res['result'] > 0) $buffer .= chr($res['result']);
            return $res;
        }
        return array('code' => AGIRES_OK, 'result' => ord($buffer{strlen($buffer) - 1}));
    }
    function fastpass_say_time(&$buffer, $time = null, $escape_digits = '')
    {
        $proceed = false;
        if ($escape_digits != '' && $buffer != '')
        {
            if (!strpos(chr(255) . $escape_digits, $buffer{strlen($buffer) - 1})) $proceed = true;
        }
        if ($buffer == '' || $proceed)
        {
            $res = $this->say_time($time, $escape_digits);
            if ($res['code'] == AGIRES_OK && $res['result'] > 0) $buffer .= chr($res['result']);
            return $res;
        }
        return array('code' => AGIRES_OK, 'result' => ord($buffer{strlen($buffer) - 1}));
    }
    function fastpass_stream_file(&$buffer, $filename, $escape_digits = '', $offset =
        0)
    {
        $proceed = false;
        if ($escape_digits != '' && $buffer != '')
        {
            if (!strpos(chr(255) . $escape_digits, $buffer{strlen($buffer) - 1})) $proceed = true;
        }
        if ($buffer == '' || $proceed)
        {
            $res = $this->stream_file($filename, $escape_digits, $offset);
            if ($res['code'] == AGIRES_OK && $res['result'] > 0) $buffer .= chr($res['result']);
            return $res;
        }
        return array(
            'code' => AGIRES_OK,
            'result' => ord($buffer{strlen($buffer) - 1}),
            'endpos' => 0);
    }
    function fastpass_text2wav(&$buffer, $text, $escape_digits = '', $frequency =
        8000)
    {
        $proceed = false;
        if ($escape_digits != '' && $buffer != '')
        {
            if (!strpos(chr(255) . $escape_digits, $buffer{strlen($buffer) - 1})) $proceed = true;
        }
        if ($buffer == '' || $proceed)
        {
            $res = $this->text2wav($text, $escape_digits, $frequency);
            if ($res['code'] == AGIRES_OK && $res['result'] > 0) $buffer .= chr($res['result']);
            return $res;
        }
        return array(
            'code' => AGIRES_OK,
            'result' => ord($buffer{strlen($buffer) - 1}),
            'endpos' => 0);
    }
    function fastpass_swift(&$buffer, $text, $escape_digits = '', $frequency = 8000,
        $voice = null)
    {
        $proceed = false;
        if ($escape_digits != '' && $buffer != '')
        {
            if (!strpos(chr(255) . $escape_digits, $buffer{strlen($buffer) - 1})) $proceed = true;
        }
        if ($buffer == '' || $proceed)
        {
            $res = $this->swift($text, $escape_digits, $frequency, $voice);
            if ($res['code'] == AGIRES_OK && $res['result'] > 0) $buffer .= chr($res['result']);
            return $res;
        }
        return array(
            'code' => AGIRES_OK,
            'result' => ord($buffer{strlen($buffer) - 1}),
            'endpos' => 0);
    }
    function fastpass_say_punctuation(&$buffer, $text, $escape_digits = '', $frequency =
        8000)
    {
        $proceed = false;
        if ($escape_digits != '' && $buffer != '')
        {
            if (!strpos(chr(255) . $escape_digits, $buffer{strlen($buffer) - 1})) $proceed = true;
        }
        if ($buffer == '' || $proceed)
        {
            $res = $this->say_punctuation($text, $escape_digits, $frequency);
            if ($res['code'] == AGIRES_OK && $res['result'] > 0) $buffer .= chr($res['result']);
            return $res;
        }
        return array('code' => AGIRES_OK, 'result' => ord($buffer{strlen($buffer) - 1}));
    }
    function fastpass_get_data(&$buffer, $filename, $timeout = null, $max_digits = null)
    {
        if (is_null($max_digits) || strlen($buffer) < $max_digits)
        {
            if ($buffer == '')
            {
                $res = $this->get_data($filename, $timeout, $max_digits);
                if ($res['code'] == AGIRES_OK) $buffer .= $res['result'];
                return $res;
            }
            else
            {
                while (is_null($max_digits) || strlen($buffer) < $max_digits)
                {
                    $res = $this->wait_for_digit();
                    if ($res['code'] != AGIRES_OK) return $res;
                    if ($res['result'] == ord('#')) break;
                    $buffer .= chr($res['result']);
                }
            }
        }
        return array('code' => AGIRES_OK, 'result' => $buffer);
    }
    function menu($choices, $timeout = 2000)
    {
        $keys = join('', array_keys($choices));
        $choice = null;
        while (is_null($choice))
        {
            foreach ($choices as $prompt)
            {
                if ($prompt{0} == '*') $ret = $this->text2wav(substr($prompt, 1), $keys);
                else  $ret = $this->stream_file($prompt, $keys);

                if ($ret['code'] != AGIRES_OK || $ret['result'] == -1)
                {
                    $choice = -1;
                    break;
                }

                if ($ret['result'] != 0)
                {
                    $choice = chr($ret['result']);
                    break;
                }
            }

            if (is_null($choice))
            {
                $ret = $this->get_data('beep', $timeout, 1);
                if ($ret['code'] != AGIRES_OK || $ret['result'] == -1) $choice = -1;
                elseif ($ret['result'] != '' && strpos(' ' . $keys, $ret['result'])) $choice = $ret['result'];
            }
        }
        return $choice;
    }
    function setContext($context, $extension = 's', $priority = 1)
    {
        $this->set_context($context);
        $this->set_extension($extension);
        $this->set_priority($priority);
    }
    function parse_callerid($callerid = null)
    {
        if (is_null($callerid)) $callerid = $this->request['agi_callerid'];

        $ret = array(
            'name' => '',
            'protocol' => '',
            'username' => '',
            'host' => '',
            'port' => '');
        $callerid = trim($callerid);

        if ($callerid{0} == '"' || $callerid{0} == "'")
        {
            $d = $callerid{0};
            $callerid = explode($d, substr($callerid, 1));
            $ret['name'] = array_shift($callerid);
            $callerid = join($d, $callerid);
        }

        $callerid = explode('@', trim($callerid, '<> '));
        $username = explode(':', array_shift($callerid));
        if (count($username) == 1) $ret['username'] = $username[0];
        else
        {
            $ret['protocol'] = array_shift($username);
            $ret['username'] = join(':', $username);
        }

        $callerid = join('@', $callerid);
        $host = explode(':', $callerid);
        if (count($host) == 1) $ret['host'] = $host[0];
        else
        {
            $ret['host'] = array_shift($host);
            $ret['port'] = join(':', $host);
        }

        return $ret;
    }
    function text2wav($text, $escape_digits = '', $frequency = 8000)
    {
        $text = trim($text);
        if ($text == '') return true;

        $hash = md5($text);
        $fname = $this->config['phpagi']['tempdir'] . DIRECTORY_SEPARATOR;
        $fname .= 'text2wav_' . $hash;

        // create wave file
        if (!file_exists("$fname.wav"))
        {
            // write text file
            if (!file_exists("$fname.txt"))
            {
                $fp = fopen("$fname.txt", 'w');
                fputs($fp, $text);
                fclose($fp);
            }

            shell_exec("{$this->config['festival']['text2wave']} -F $frequency -o $fname.wav $fname.txt");
        }
        else
        {
            touch("$fname.txt");
            touch("$fname.wav");
        }

        // stream it
        $ret = $this->stream_file($fname, $escape_digits);

        // clean up old files
        $delete = time() - 2592000; // 1 month
        foreach (glob($this->config['phpagi']['tempdir'] . DIRECTORY_SEPARATOR .
            'text2wav_*') as $file)
            if (filemtime($file) < $delete) unlink($file);

        return $ret;
    }
    function swift($text, $escape_digits = '', $frequency = 8000, $voice = null)
    {
        if (!is_null($voice)) $voice = "-n $voice";
        elseif (isset($this->config['cepstral']['voice'])) $voice = "-n {$this->config['cepstral']['voice']}";

        $text = trim($text);
        if ($text == '') return true;

        $hash = md5($text);
        $fname = $this->config['phpagi']['tempdir'] . DIRECTORY_SEPARATOR;
        $fname .= 'swift_' . $hash;

        // create wave file
        if (!file_exists("$fname.wav"))
        {
            // write text file
            if (!file_exists("$fname.txt"))
            {
                $fp = fopen("$fname.txt", 'w');
                fputs($fp, $text);
                fclose($fp);
            }

            shell_exec("{$this->config['cepstral']['swift']} -p audio/channels=1,audio/sampling-rate=$frequency $voice -o $fname.wav -f $fname.txt");
        }

        // stream it
        $ret = $this->stream_file($fname, $escape_digits);

        // clean up old files
        $delete = time() - 2592000; // 1 month
        foreach (glob($this->config['phpagi']['tempdir'] . DIRECTORY_SEPARATOR .
            'swift_*') as $file)
            if (filemtime($file) < $delete) unlink($file);

        return $ret;
    }
    function text_input($mode = 'NUMERIC')
    {
        $alpha = array(
            'k0' => ' ',
            'k00' => ',',
            'k000' => '.',
            'k0000' => '?',
            'k00000' => '0',
            'k1' => '!',
            'k11' => ':',
            'k111' => ';',
            'k1111' => '#',
            'k11111' => '1',
            'k2' => 'A',
            'k22' => 'B',
            'k222' => 'C',
            'k2222' => '2',
            'k3' => 'D',
            'k33' => 'E',
            'k333' => 'F',
            'k3333' => '3',
            'k4' => 'G',
            'k44' => 'H',
            'k444' => 'I',
            'k4444' => '4',
            'k5' => 'J',
            'k55' => 'K',
            'k555' => 'L',
            'k5555' => '5',
            'k6' => 'M',
            'k66' => 'N',
            'k666' => 'O',
            'k6666' => '6',
            'k7' => 'P',
            'k77' => 'Q',
            'k777' => 'R',
            'k7777' => 'S',
            'k77777' => '7',
            'k8' => 'T',
            'k88' => 'U',
            'k888' => 'V',
            'k8888' => '8',
            'k9' => 'W',
            'k99' => 'X',
            'k999' => 'Y',
            'k9999' => 'Z',
            'k99999' => '9');
        $symbol = array(
            'k0' => '=',
            'k1' => '<',
            'k11' => '(',
            'k111' => '[',
            'k1111' => '{',
            'k11111' => '1',
            'k2' => '@',
            'k22' => '$',
            'k222' => '&',
            'k2222' => '%',
            'k22222' => '2',
            'k3' => '>',
            'k33' => ')',
            'k333' => ']',
            'k3333' => '}',
            'k33333' => '3',
            'k4' => '+',
            'k44' => '-',
            'k444' => '*',
            'k4444' => '/',
            'k44444' => '4',
            'k5' => "'",
            'k55' => '`',
            'k555' => '5',
            'k6' => '"',
            'k66' => '6',
            'k7' => '^',
            'k77' => '7',
            'k8' => "\\",
            'k88' => '|',
            'k888' => '8',
            'k9' => '_',
            'k99' => '~',
            'k999' => '9');
        $text = '';
        do
        {
            $command = false;
            $result = $this->get_data('beep');
            foreach (explode('*', $result['result']) as $code)
            {
                if ($command)
                {
                    switch ($code{0})
                    {
                        case '2':
                            $text = substr($text, 0, strlen($text) - 1);
                            break; // backspace
                        case '5':
                            $mode = 'LOWERCASE';
                            break;
                        case '6':
                            $mode = 'NUMERIC';
                            break;
                        case '7':
                            $mode = 'SYMBOL';
                            break;
                        case '8':
                            $mode = 'UPPERCASE';
                            break;
                        case '9':
                            $text = explode(' ', $text);
                            unset($text[count($text) - 1]);
                            $text = join(' ', $text);
                            break; // backspace a word
                    }
                    $code = substr($code, 1);
                    $command = false;
                }
                if ($code == '') $command = true;
                elseif ($mode == 'NUMERIC') $text .= $code;
                elseif ($mode == 'UPPERCASE' && isset($alpha['k' . $code])) $text .= $alpha['k' .
                        $code];
                elseif ($mode == 'LOWERCASE' && isset($alpha['k' . $code])) $text .= strtolower($alpha['k' .
                        $code]);
                elseif ($mode == 'SYMBOL' && isset($symbol['k' . $code])) $text .= $symbol['k' .
                        $code];
            }
            $this->say_punctuation($text);
        } while (substr($result['result'], -2) == '**');
        return $text;
    }
    function say_punctuation($text, $escape_digits = '', $frequency = 8000)
    {
        $ret = "";
        for ($i = 0; $i < strlen($text); $i++)
        {
            switch ($text{$i})
            {
                case ' ':
                    $ret .= 'SPACE ';
                case ',':
                    $ret .= 'COMMA ';
                    break;
                case '.':
                    $ret .= 'PERIOD ';
                    break;
                case '?':
                    $ret .= 'QUESTION MARK ';
                    break;
                case '!':
                    $ret .= 'EXPLANATION POINT ';
                    break;
                case ':':
                    $ret .= 'COLON ';
                    break;
                case ';':
                    $ret .= 'SEMICOLON ';
                    break;
                case '#':
                    $ret .= 'POUND ';
                    break;
                case '=':
                    $ret .= 'EQUALS ';
                    break;
                case '<':
                    $ret .= 'LESS THAN ';
                    break;
                case '(':
                    $ret .= 'LEFT PARENTHESIS ';
                    break;
                case '[':
                    $ret .= 'LEFT BRACKET ';
                    break;
                case '{':
                    $ret .= 'LEFT BRACE ';
                    break;
                case '@':
                    $ret .= 'AT ';
                    break;
                case '$':
                    $ret .= 'DOLLAR SIGN ';
                    break;
                case '&':
                    $ret .= 'AMPERSAND ';
                    break;
                case '%':
                    $ret .= 'PERCENT ';
                    break;
                case '>':
                    $ret .= 'GREATER THAN ';
                    break;
                case ')':
                    $ret .= 'RIGHT PARENTHESIS ';
                    break;
                case ']':
                    $ret .= 'RIGHT BRACKET ';
                    break;
                case '}':
                    $ret .= 'RIGHT BRACE ';
                    break;
                case '+':
                    $ret .= 'PLUS ';
                    break;
                case '-':
                    $ret .= 'MINUS ';
                    break;
                case '*':
                    $ret .= 'ASTERISK ';
                    break;
                case '/':
                    $ret .= 'SLASH ';
                    break;
                case "'":
                    $ret .= 'SINGLE QUOTE ';
                    break;
                case '`':
                    $ret .= 'BACK TICK ';
                    break;
                case '"':
                    $ret .= 'QUOTE ';
                    break;
                case '^':
                    $ret .= 'CAROT ';
                    break;
                case "\\":
                    $ret .= 'BACK SLASH ';
                    break;
                case '|':
                    $ret .= 'BAR ';
                    break;
                case '_':
                    $ret .= 'UNDERSCORE ';
                    break;
                case '~':
                    $ret .= 'TILDE ';
                    break;
                default:
                    $ret .= $text{$i} . ' ';
                    break;
            }
        }
        return $this->text2wav($ret, $escape_digits, $frequency);
    }
    function evaluate($command)
    {
        $broken = array(
            'code' => 500,
            'result' => -1,
            'data' => '');

        // write command
        if (!@fwrite($this->out, trim($command) . "\n")) return $broken;
        fflush($this->out);

        // Read result.  Occasionally, a command return a string followed by an extra new line.
        // When this happens, our script will ignore the new line, but it will still be in the
        // buffer.  So, if we get a blank line, it is probably the result of a previous
        // command.  We read until we get a valid result or asterisk hangs up.  One offending
        // command is SEND TEXT.
        $count = 0;
        do
        {
            $str = trim(fgets($this->in, 4096));
        } while ($str == '' && $count++ < 5);

        if ($count >= 5)
        {
            //          $this->conlog("evaluate error on read for $command");
            return $broken;
        }

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
            if ($count >= 5)
            {
                //            $this->conlog("evaluate error on multiline read for $command");
                return $broken;
            }
        }

        $ret['result'] = null;
        $ret['data'] = '';
        if ($ret['code'] != AGIRES_OK) // some sort of error
        {
            $ret['data'] = $str;
            $this->conlog(print_r($ret, true));
        }
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

        // log some errors
        if ($ret['result'] < 0) $this->conlog("$command returned {$ret['result']}");

        return $ret;
    }
    function conlog($str, $vbl = 1)
    {
        static $busy = false;

        if ($this->config['phpagi']['debug'] != false)
        {
            if (!$busy) // no conlogs inside conlog!!!
            {
                $busy = true;
                $this->verbose($str, $vbl);
                $busy = false;
            }
        }
    }
    function which($cmd, $checkpath = null)
    {
        global $_ENV;
        # $chpath = is_null($checkpath) ? $_ENV['PATH'] : $checkpath;
        $chpath = (is_null($checkpath) && isset($_ENV['PATH'])) ? $_ENV['PATH'] : $checkpath;

        foreach (explode(':', $chpath) as $path)
            if (is_executable("$path/$cmd")) return "$path/$cmd";

        if (is_null($checkpath)) return $this->which($cmd,
                '/bin:/sbin:/usr/bin:/usr/sbin:/usr/local/bin:/usr/local/sbin:' .
                '/usr/X11R6/bin:/usr/local/apache/bin:/usr/local/mysql/bin');
        return false;
    }
    function make_folder($folder, $perms = 0755)
    {
        $f = explode(DIRECTORY_SEPARATOR, $folder);
        $base = '';
        for ($i = 0; $i < count($f); $i++)
        {
            $base .= $f[$i];
            if ($f[$i] != '' && !file_exists($base))
            {
                if (mkdir($base, $perms) == false)
                {
                    return (false);
                }
            }
            $base .= DIRECTORY_SEPARATOR;
        }
        return (true);
    }

}


function phpagi_error_handler($level, $message, $file, $line, $context)
{
    if (ini_get('error_reporting') == 0) return; // this happens with an @

    @syslog(LOG_WARNING, $file . '[' . $line . ']: ' . $message);

    global $phpagi_error_handler_email;
    if (function_exists('mail') && !is_null($phpagi_error_handler_email))
        // generate email debugging information
    {
        // decode error level
        switch ($level)
        {
            case E_WARNING:
            case E_USER_WARNING:
                $level = "Warning";
                break;
            case E_NOTICE:
            case E_USER_NOTICE:
                $level = "Notice";
                break;
            case E_USER_ERROR:
                $level = "Error";
                break;
        }

        // build message
        $basefile = basename($file);
        $subject = "$basefile/$line/$level: $message";
        $message = "$level: $message in $file on line $line\n\n";

        if (function_exists('mysql_errno') && strpos(' ' . strtolower($message), 'mysql')) 
                $message .= 'MySQL error ' . mysql_errno() . ": " . mysql_error() . "\n\n";

        // figure out who we are
        if (function_exists('socket_create'))
        {
            $addr = null;
            $port = 80;
            $socket = @socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
            @socket_connect($socket, '64.0.0.0', $port);
            @socket_getsockname($socket, $addr, $port);
            @socket_close($socket);
            $message .= "\n\nIP Address: $addr\n";
        }

        // include variables
        $message .= "\n\nContext:\n" . print_r($context, true);
        $message .= "\n\nGLOBALS:\n" . print_r($GLOBALS, true);
        $message .= "\n\nBacktrace:\n" . print_r(debug_backtrace(), true);

        // include code fragment
        if (file_exists($file))
        {
            $message .= "\n\n$file:\n";
            $code = @file($file);
            for ($i = max(0, $line - 10); $i < min($line + 10, count($code)); $i++) $message .= ($i +
                    1) . "\t$code[$i]";
        }

        // make sure message is fully readable (convert unprintable chars to hex representation)
        $ret = '';
        for ($i = 0; $i < strlen($message); $i++)
        {
            $c = ord($message{$i});
            if ($c == 10 || $c == 13 || $c == 9) $ret .= $message{$i};
            elseif ($c < 16) $ret .= '\x0' . dechex($c);
            elseif ($c < 32 || $c > 127) $ret .= '\x' . dechex($c);
            else  $ret .= $message{$i};
        }
        $message = $ret;

        // send the mail if less than 5 errors
        static $mailcount = 0;
        if ($mailcount < 5) @mail($phpagi_error_handler_email, $subject, $message);
        $mailcount++;
    }
}

$phpagi_error_handler_email = null;
