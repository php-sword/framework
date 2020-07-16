<?php
declare(strict_types=1);
/**
 * This file is part of Sword.
 * @link     http://sword.kyour.cn
 * @document http://sword.kyour.cn/doc
 * @contact  kyour@vip.qq.com
 * @license  http://github.com/php-sword/sword/blob/master/LICENSE
 */

namespace sword;

use Redis as RedisD;

/**
 * Class Redis
 *
 * redis管理类
 */
class Redis
{

    private static $connections = []; //定义一个对象池
    private static $servers = []; //定义redis配置文件

    //定义添加redis配置方法
    public static function addServer($conf)
    {
        foreach ($conf as $alias => $data){
            self::$servers[$alias]=$data;
        }
    }

    //两个参数要连接的服务器KEY,要选择的库
    public static function getRedis($alias,$select = 0)
    {
        if(!array_key_exists($alias,self::$connections)){  //判断连接池中是否存在
            $redis = new RedisD();
            $redis->connect(self::$servers[$alias][0],self::$servers[$alias][1]);
            self::$connections[$alias]=$redis;
            if(isset(self::$servers[$alias][2]) && self::$servers[$alias][2]!=""){
                self::$connections[$alias]->auth(self::$servers[$alias][2]);
            }
        }
        self::$connections[$alias]->select($select);
        return self::$connections[$alias];
    }

    //连接RA，使用默认0库
    public static function set($key, $data)
    {

        $redis = self::getRedis('RA');

        return $redis ->set($key, $data);
    }

    public static function get($key)
    {

        $redis = self::getRedis('RA'); //连接RA，使用默认0库

        return $redis ->get($key);
    }

}
