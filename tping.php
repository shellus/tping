<?php
/**
 * Created by PhpStorm.
 * User: shellus
 * Date: 2017/2/6
 * Time: 13:54
 */


function pingFsockopen($host, $port, $errno = 0, $errstr = '')
{
    $start = microtime(true);
    $fp = @fsockopen($host, $port, $errno, $errstr);
    if (!$fp) {
        $latency = false;
    } else {
        $latency = microtime(true) - $start;
        $latency = round($latency * 1000);
        fclose($fp);
    }
    return $latency;
}

function isArguments($str)
{
    return substr($str, 0, 2) == '--' || substr($str, 0, 1) == '-';
}

function removeArgumentsGap($str)
{
    $pos = 0;
    if (substr($str, 0, 2) == '--') {
        $pos = 2;
    } elseif (substr($str, 0, 1) == '-') {
        $pos = 1;
    }
    return substr($str, $pos);
}

/**
 * @param $argv
 * @return array
 */
function parseCommandLineArgs()
{
    global $argv;
    $args = array();
    for ($i = 0; $i < count($argv); $i++) {
        if (isArguments($argv[$i])) {
            $args[removeArgumentsGap($argv[$i])] = [];
            $lastArr = &$args[removeArgumentsGap($argv[$i])];
        } else {
            $lastArr[] = $argv[$i];
        }
    }
    return $args;
}

array_shift($argv);

$args = parseCommandLineArgs();

function config($key, $default = null)
{
    global $args;
    if (!key_exists($key, $args)) {
        return $default;
    } else {
        $value = $args[$key];
        if ($value) {
            if (count($value) == 1) {
                // string
                return $value[0];
            } else {
                // array
                return $value;
            }
        } elseif (is_array($value)) {
            // bool
            return true;
        }
    }
}

//var_dump($args);

$c = 4;
$EOL = PHP_EOL;
$success = 0;
$fail = 0;
$latencys = array();
for ($i = 0; $i < $c; $i++) {
    $latency = pingFsockopen(config('h'), config('p'));
    if ($latency === false) {
        $fail++;
        $str = "无法建立连接{$EOL}";
    }else{
        $success++;
        $latencys[] = $latency;
        $str = "对 ".config('h')." 进行的TCP连接测试: 时间={$latency}ms{$EOL}";
    }
    echo $str;

    // 等待网络恢复
    sleep(1);
}
echo $EOL;
$loss = ($fail/$c) * 100;
echo config('h')." 的 TCP 连接测试统计信息:" . " 数据包:{$EOL}    已发送 = {$c}，已接收 = {$success}，丢失 = {$fail} ({$loss}% 丢失){$EOL}";

sort($latencys);
$pre = array_sum($latencys) / count($latencys);
echo "往返行程的估计时间(以毫秒为单位):{$EOL}    最短 = ".end($latencys)."ms，最长 = ".reset($latencys)."ms，平均 = {$pre}ms{$EOL}";
echo $EOL;