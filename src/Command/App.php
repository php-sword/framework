<?php


namespace Sword\Command;


use EasySwoole\Command\AbstractInterface\CommandInterface;
use EasySwoole\Command\CommandManager;

class App implements CommandInterface
{
    public function commandName(): string
    {
        return 'app';
    }

    public function desc(): string
    {
        return '用户自定义';
    }

    public function exec(): string
    {
        $manager = CommandManager::getInstance();
        /** 获取原始未变化的argv */
        $manager->getOriginArgv();
//        var_dump($manager->getOriginArgv());

        /**
         * 经过处理的数据
         * 比如 1 2 3 a=1 aa=123
         * 处理之后就变成[1, 2, 3, 'a' => 1, 'aa' => 123]
         */
        $manager->getArgs();

        /**
         * 获取选项
         * 比如 --config=dev -d
         * 处理之后就是['config' => 'dev', 'd' => true]
         */
        $manager->getOpts();

        /**
         * 根据下标或者键来获取值
         */
        $manager->getArg('a');
//        var_dump($manager->getArg('a'));

        /**
         * 根据键来获取选项
         */
        $manager->getOpt('config');

        /**
         * 检测在args中是否存在该下标或者键
         */
        $manager->issetArg(1);

        /**
         * 检测在opts中是否存在该键
         */
        $manager->issetOpt('test');

        return '自定义命令行执行方法';
    }

    public function help(\EasySwoole\Command\AbstractInterface\CommandHelpInterface $commandHelp): \EasySwoole\Command\AbstractInterface\CommandHelpInterface
    {
        $commandHelp->addAction('test','测试方法');
        $commandHelp->addActionOpt('-no','不输出详细信息');
        return $commandHelp;
    }
}