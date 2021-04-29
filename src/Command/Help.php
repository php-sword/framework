<?php declare(strict_types=1);
/**
 * This file is part of Sword.
 * @link     http://sword.kyour.cn
 * @document http://sword.kyour.cn/doc
 * @contact  kyour@vip.qq.com
 * @license  http://github.com/php-sword/sword/blob/master/LICENSE
 */
namespace Sword\Command;

use EasySwoole\Command\AbstractInterface\CommandInterface;
use EasySwoole\Command\CommandManager;

/**
 * Class Help
 * @package Sword\Command
 */
class Help implements CommandInterface
{
    public function commandName(): string
    {
        return 'help';
    }

    public function desc(): string
    {
        return 'Display this help message';
    }

    public function exec(): string
    {

//        $manager = CommandManager::getInstance();

        return 'Sword 框架使用帮助 -暂无描述';
    }

    public function help(\EasySwoole\Command\AbstractInterface\CommandHelpInterface $commandHelp): \EasySwoole\Command\AbstractInterface\CommandHelpInterface
    {
//        $commandHelp->addAction('test','测试方法');
//        $commandHelp->addActionOpt('-no','不输出详细信息');
        return $commandHelp;
    }
}