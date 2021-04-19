<?php
/**
 * This file is part of Sword.
 * @link     http://sword.kyour.cn
 * @document http://sword.kyour.cn/doc
 * @contact  kyour@vip.qq.com
 * @license  http://github.com/php-sword/sword/blob/master/LICENSE
 */

namespace Sword;

use App\WebSocket\WebSocketParser;
use EasySwoole\Command\CommandManager;

use EasySwoole\EasySwoole\Command\CommandRunner;
use EasySwoole\Command\Caller;
use EasySwoole\EasySwoole\ServerManager;
use EasySwoole\EasySwoole\Swoole\EventRegister;
use EasySwoole\Redis\Config\RedisConfig;
use EasySwoole\Socket\Dispatcher;
use EasySwoole\Template\Render;
use Sword\Component\Template\ThinkTemplateRender;
use Sword\Component\WebSocket\WebSocketJsonParser;

class SwordEvent
{
    /**
     * 入口启动事件
     * 发生在框架初始化之前
     */
    public static function bootstrap(array $argv)
    {
        // 加载助手函数
        require_once __DIR__."/helper.php";

        /**
         * Easyswoole框架初始化
         * 版本 3.4
         */

        defined('IN_PHAR') or define('IN_PHAR', boolval(\Phar::running(false)));
        defined('RUNNING_ROOT') or define('RUNNING_ROOT', ROOT_PATH);
        defined('EASYSWOOLE_ROOT') or define('EASYSWOOLE_ROOT', IN_PHAR ? \Phar::running() : ROOT_PATH);

        if(file_exists(EASYSWOOLE_ROOT.'/bootstrap.php')){
            require_once EASYSWOOLE_ROOT.'/bootstrap.php';
        }

        $caller = new Caller();
        $caller->setScript(current($argv));
        $caller->setCommand(next($argv));
        $caller->setParams($argv);
        reset($argv);

        $ret = CommandRunner::getInstance()->run($caller);
        if($ret && !empty($ret->getMsg())){
            echo $ret->getMsg()."\n";
        }

        // 时区设置
        date_default_timezone_set(config('app.timezone') ?: 'Asia/Shanghai');

        // 添加命令行
        CommandManager::getInstance()->addCommand(new \Sword\Command\Help());

    }

    public static function initialize()
    {

        // -------------------- DB --------------------
        //创建数据库连接池注册
        $db_conf = config('database');
        if($db_conf['enable']) {
            $dbConfig = new \EasySwoole\ORM\Db\Config();
            //数据库配置
            $dbConfig->setDatabase($db_conf['name'])
                ->setUser($db_conf['user'])
                ->setPassword($db_conf['password'])
                ->setHost($db_conf['host'])
                ->setPort($db_conf['port'])
                ->setCharset($db_conf['charset'])
                //连接池配置
                ->setGetObjectTimeout(3.0) //设置获取连接池对象超时时间
                ->setIntervalCheckTime(30 * 1000) //设置检测连接存活执行回收和创建的周期
                ->setMaxIdleTime(15) //连接池对象最大闲置时间(秒)
                ->setMaxObjectNum(50) //设置最大连接池存在连接对象数量
                ->setMinObjectNum(10) //设置最小连接池存在连接对象数量
                ->setAutoPing(5); //设置自动ping客户端链接的间隔

            \EasySwoole\ORM\DbManager::getInstance()->addConnection(new \EasySwoole\ORM\Db\Connection($dbConfig));
        }
        // -------------------- DB END --------------------

        // -------------------- REDIS --------------------
        //redis连接池注册
        $redis_conf = config('redis');
        if($redis_conf['enable']){
            \EasySwoole\RedisPool\RedisPool::getInstance()
                ->register(new RedisConfig([
                    'host'      => $redis_conf['host'],
                    'port'      => $redis_conf['port'],
                    'auth'      => $redis_conf['password'],
                    'serialize' => \EasySwoole\Redis\Config\RedisConfig::SERIALIZE_NONE,
                    'db'        => $redis_conf['db']
                ]));
        }
        // -------------------- REDIS END --------------------

    }

    public static function mainServerCreate(EventRegister $register)
    {

        $app_conf = config('app');

        /**
         * **************** 热重载 **********************
         */
        if(!empty($app_conf['hot_reload'])){
            // 配置同上别忘了添加要检视的目录
            $hotReloadOptions = new \EasySwoole\HotReload\HotReloadOptions;
            $hotReload = new \EasySwoole\HotReload\HotReload($hotReloadOptions);
            $hotReloadOptions->setMonitorFolder([EASYSWOOLE_ROOT . '/App']);

            $server = ServerManager::getInstance()->getSwooleServer();
            $hotReload->attachToServer($server);
        }

        /**
         * **************** websocket控制器 **********************
         */
        if(!empty($app_conf['enable_ws'])) {
            // 创建一个 Dispatcher 配置
            $conf = new \EasySwoole\Socket\Config();
            // 设置 Dispatcher 为 WebSocket 模式
            $conf->setType(\EasySwoole\Socket\Config::WEB_SOCKET);
            // 设置解析器对象
            $conf->setParser(new WebSocketJsonParser());
            // 创建 Dispatcher 对象 并注入 config 对象
            $dispatch = new Dispatcher($conf);
            // 给server 注册相关事件 在 WebSocket 模式下  on message 事件必须注册 并且交给 Dispatcher 对象处理
            $register->set(EventRegister::onMessage, function (\Swoole\Server $server, \Swoole\WebSocket\Frame $frame) use ($dispatch) {
                $dispatch->dispatch($server, $frame->data, $frame);
            });
        }

        /**
         * **************** 模板引擎 **********************
         * -在全局的主服务中创建事件中，实例化该Render,并注入你的驱动配置
         */
        $view_conf = config('view');
        if(!empty($view_conf['enable'])){
            $render = Render::getInstance();
            if($view_conf['engine'] == 'think'){
                $render->getConfig()->setRender(new ThinkTemplateRender());
            }
            $render->getConfig()->setTempDir(EASYSWOOLE_TEMP_DIR);
            $render->attachServer(ServerManager::getInstance()->getSwooleServer());
        }

    }

}
