<?php
/**
 * This file is part of Sword.
 * @link     http://sword.kyour.cn
 * @document http://sword.kyour.cn/doc
 * @contact  kyour@vip.qq.com
 * @license  http://github.com/php-sword/sword/blob/master/LICENSE
 */

namespace Sword;

use EasySwoole\Command\CommandManager;

use EasySwoole\EasySwoole\Command\CommandRunner;
use EasySwoole\Command\Caller;

class SwordEvent
{
    /**
     * 入口启动事件
     * 发生在框架初始化之前
     */
    public static function bootstrap(array $argv)
    {
        // 加载助手函数
        require_once __DIR__."/helper.php";

        // 底层框架初始化
        // require_once __DIR__."/initialize.php";

        /**
         * Easyswoole框架初始化
         * 版本 3.4
         */

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


        // 时区设置
        date_default_timezone_set(config('app.timezone') ?: 'Asia/Shanghai');

        // 添加命令行
        CommandManager::getInstance()->addCommand(new \Sword\Command\Help());

    }

    public static function initialize()
    {

    }

    public static function mainServerCreate()
    {

    }

}
