<?php

namespace Sword;

class Log
{
    //获取es日志实列
    public static function get()
    {
        return \EasySwoole\EasySwoole\Logger::getInstance();
    }

}