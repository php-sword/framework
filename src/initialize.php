<?php declare(strict_types=1);
/**
 * Easyswoole框架初始化
 * 版本 3.4
 */
use EasySwoole\EasySwoole\Command\CommandRunner;
use EasySwoole\Command\Caller;

defined('IN_PHAR') or define('IN_PHAR', boolval(\Phar::running(false)));
defined('RUNNING_ROOT') or define('RUNNING_ROOT', ROOT_PATH);
defined('EASYSWOOLE_ROOT') or define('EASYSWOOLE_ROOT', IN_PHAR ? \Phar::running() : ROOT_PATH);

if(file_exists(EASYSWOOLE_ROOT.'/bootstrap.php')){
    require_once EASYSWOOLE_ROOT.'/bootstrap.php';
}

$caller = new Caller();
$caller->setScript(current($argv));
$caller->setCommand(next($argv));
$caller->setParams($argv);
reset($argv);

$ret = CommandRunner::getInstance()->run($caller);
if($ret && !empty($ret->getMsg())){
    echo $ret->getMsg()."\n";
}
