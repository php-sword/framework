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

class SwordEvent
{
    /**
     * 入口启动事件
     * 发生在框架初始化之前
     */
    public static function bootstrap()
    {
        // 加载助手函数
        require_once __DIR__."/helper.php";

        // 底层框架初始化
        require_once __DIR__."/initialize.php";

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
