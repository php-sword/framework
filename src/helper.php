<?php
declare(strict_types=1);
/**
 * This file is part of Sword.
 * @link     http://sword.kyour.cn
 * @document http://sword.kyour.cn/doc
 * @contact  kyour@vip.qq.com
 * @license  http://github.com/php-sword/sword/blob/master/LICENSE
 */

use sword\driver\Config;

/**
 * Sword框架助手函数
 */


define('SWORD_NULL',"SWORD_NULL_VALUE");

//容器
$sword_container = [];

if (!function_exists('container')) {
    /**
     * 获取容器中App实例
     */
    function container($name = SWORD_NULL, $value = SWORD_NULL)
    {

        global $sword_container;

        // 无参数时获取所有
        if($name === SWORD_NULL or is_null($name)){
            return $sword_container;
        }

        //取容器中某一个实例,使用SWORD_NULL是为了避免value传入的是null
        if($value === SWORD_NULL){
            return isset($sword_container[$name])?$sword_container[$name]:null;
        }

        //设置容器
        $sword_container[$name] = $value;

        return true;

    }
}

if (!function_exists('app')) {
    /**
     * 获取容器中App实例
     */
    function app($app = SWORD_NULL)
    {
        if($app === SWORD_NULL)
            return container('sword_app');

        container('sword_app',$app);
    }
}

if (!function_exists('config')) {
    /**
     * 获取容器中App实例
     */
    function config($name = SWORD_NULL)
    {
        
        global $sword_container;
        //判断容器当中是否已载入配置
        if(!isset($sword_container['sword_config'])){
            //载入文件
            $config = Config::loadFile();
            $sword_container['sword_config'] = $config;
        }

        return Config::get($name);

    }
}

