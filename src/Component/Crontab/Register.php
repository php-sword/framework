<?php declare(strict_types=1);
/**
 * This file is part of Sword.
 * @link     http://sword.kyour.cn
 * @document http://sword.kyour.cn/doc
 * @contact  kyour@vip.qq.com
 * @license  http://github.com/php-sword/sword/blob/master/LICENSE
 */
namespace Sword\Component\Crontab;

use EasySwoole\EasySwoole\Crontab\Crontab;

class Register
{
    /**
     * @var array 注册开启的定时任务类
     */
    protected $className = [];

    /**
     * Register constructor.
     */
    function __construct()
    {
        //批量注册任务
        foreach ($this->className as $c) {
            Crontab::getInstance()->addTask($c);
        }

        //定义其他任务
        $this->taskCreate();

    }

    /**
     * 其他Task创建
     */
    public function taskCreate()
    {
        // 继承类实现
    }

}