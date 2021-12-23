<?php

namespace Sword;

use EasySwoole\EasySwoole\Logger;

class Log
{
    //获取es日志实列
    public static function get()
    {
        return Logger::getInstance();
    }

}