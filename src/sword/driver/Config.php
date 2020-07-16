<?php
declare(strict_types=1);
/**
 * This file is part of Sword.
 * @link     http://sword.kyour.cn
 * @document http://sword.kyour.cn/doc
 * @contact  kyour@vip.qq.com
 * @license  http://github.com/php-sword/sword/blob/master/LICENSE
 */
 
namespace sword\driver;

/**
 * Class Config
 *
 * Sword配置管理类
 */
class Config
{
    /**
     * 加载全部配置文件
     */
    public static function loadFile()
    {
        $path = APP_PATH . DIRECTORY_SEPARATOR . 'config';
        $config = [];
        //取出配置目录全部文件
        foreach(scandir($path) as $file){
            //如果是php文件
            if(preg_match('/.php/',$file)){
                //获取配置内容
                $arr = require $path . DIRECTORY_SEPARATOR . $file;
                //存入数组
                $config[strtolower(basename($file,".php"))] = $arr;

            }
        }
        //返回全部内容
        return $config;
    }

    /**
     * 获取配置参数 为空则获取所有配置
     * @access public
     * @param  string $name    配置参数名（支持多级配置 .号分割）
     * @param  mixed  $default 默认值
     * @return mixed
     */
    public static function get(string $name = SWORD_NULL, $default = null)
    {
        $config = container('sword_config');
        // 无参数时获取所有
        if ($name === SWORD_NULL) {
            return $config;
        }

        if (false === strpos($name, '.')) {
            return $config[$name];
        }

        $name    = explode('.', $name);
        $name[0] = strtolower($name[0]);

        // 按.拆分成多维数组进行判断
        foreach ($name as $val) {
            if (isset($config[$val])) {
                $config = $config[$val];
            } else {
                return $default;
            }
        }

        return $config;
    }
}