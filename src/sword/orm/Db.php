<?php
declare(strict_types=1);
/**
 * This file is part of Sword.
 * @link     http://sword.kyour.cn
 * @document http://sword.kyour.cn/doc
 * @contact  kyour@vip.qq.com
 * @license  http://github.com/php-sword/sword/blob/master/LICENSE
 */

namespace sword\orm;

use sword\orm\db\Query;
use EasySwoole\ORM\DbManager;
use EasySwoole\Mysqli\QueryBuilder;

/**
 * Class Db
 * @package sethink\swooleOrm
 * @method Query init(string $server) static 初始化，加入server
 */
class Db
{
    //获取数据库实例
    public static function getManager()
    {
        return DbManager::getInstance();
    }

    //生成QueryBuilder 对象
    public static function builder(string $sql,array $bind)
    {
        $queryBuild = new QueryBuilder();
        $queryBuild->raw($sql, $bind);
        return $queryBuild;
    }

    public static function __callStatic($method, $args)
    {
        $class = Query::class;
        return call_user_func_array([new $class, $method], $args);
    }

}