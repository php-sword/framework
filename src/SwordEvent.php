<?php
/**
 * This file is part of Sword.
 * @link     http://sword.kyour.cn
 * @document http://sword.kyour.cn/doc
 * @contact  kyour@vip.qq.com
 * @license  http://github.com/php-sword/sword/blob/master/LICENSE
 */
namespace Sword;

use EasySwoole\Component\Di;
use EasySwoole\EasySwoole\Command\CommandRunner;
use EasySwoole\Command\Caller;
use EasySwoole\EasySwoole\Crontab\Crontab;
use EasySwoole\EasySwoole\ServerManager;
use EasySwoole\EasySwoole\Swoole\EventRegister;
use EasySwoole\EasySwoole\SysConst;
use EasySwoole\Http\Request;
use EasySwoole\Http\Response;
use EasySwoole\Redis\Config\RedisConfig;
use EasySwoole\Session\FileSession;
use EasySwoole\Socket\Dispatcher;
use EasySwoole\Template\Render;
use EasySwoole\Utility\Random;
use Sword\Component\Session\Session;
use Sword\Component\Template\ThinkTemplateRender;
use Sword\Component\WebSocket\WebSocketJsonParser;
use EasySwoole\FileWatcher\FileWatcher;
use EasySwoole\FileWatcher\WatchRule;

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
         * Easyswoole框架入口文件
         * 版本 3.4
         */
        defined('IN_PHAR') or define('IN_PHAR', boolval(\Phar::running(false)));
        defined('RUNNING_ROOT') or define('RUNNING_ROOT', ROOT_PATH);
        defined('EASYSWOOLE_ROOT') or define('EASYSWOOLE_ROOT', IN_PHAR ? \Phar::running() : ROOT_PATH);

        // 时区设置
        date_default_timezone_set(config('app.timezone') ?: 'Asia/Shanghai');

        // 添加命令行
        \EasySwoole\Command\CommandManager::getInstance()->addCommand(new \Sword\Command\Nginx());

        //执行bootstrap文件
        if(file_exists(EASYSWOOLE_ROOT.'/bootstrap.php')){
            require_once EASYSWOOLE_ROOT.'/bootstrap.php';
        }

        // Easyswoole 命令行入口
        $caller = new Caller();
        $caller->setScript(current($argv));
        $caller->setCommand(next($argv));
        $caller->setParams($argv);
        reset($argv);
        $ret = CommandRunner::getInstance()->run($caller);
        if($ret && !empty($ret->getMsg())){
            echo $ret->getMsg()."\n";
        }

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

        ob_start();
    }

    public static function mainServerCreate(EventRegister $register)
    {
        ob_end_clean();
        self::logoSword();

        $app_conf = config('app');

        /**
         * **************** Crontab定时任务 **********************
         */
        $path = EASYSWOOLE_ROOT .'/App/Crontab';
        //取出配置目录全部文件
        foreach(scandir($path) as $file){
            //如果是php文件
            if(preg_match('/.php/',$file)){
                $name = basename($file,".php");
                $class = "\\App\\Crontab\\{$name}";
                if(class_exists($class) and $class::enable){
                    Crontab::getInstance()->addTask($class);
                }
            }
        }

        /**
         * **************** Process自定义进程 **********************
         */
        $path = EASYSWOOLE_ROOT .'/App/Process';
        //取出配置目录全部文件
        foreach(scandir($path) as $file){
            //如果是php文件
            if(preg_match('/.php/',$file)){
                $name = basename($file,".php");
                $class = "\\App\\Process\\{$name}";
                if(class_exists($class) and $class::enable){
                    $config = new \EasySwoole\Component\Process\Config([
                        'processName' => $name, // 设置进程名称
                    ]);
                    $process = new $class($config);
                    \EasySwoole\Component\Process\Manager::getInstance()->addProcess($process);
                }
            }
        }

        /**
         * **************** 热重载 **********************
         */
        if(!empty($app_conf['hot_reload'])){
            $watcher = new FileWatcher();
            $rule = new WatchRule(EASYSWOOLE_ROOT . "/App"); // 设置监控规则和监控目录
            $watcher->addRule($rule);
            $watcher->setOnChange(function () {
                Log::get()->info('file change ,reload!!!');
                ServerManager::getInstance()->getSwooleServer()->reload();
            });
            $watcher->attachServer(ServerManager::getInstance()->getSwooleServer());
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
         * **************** 启动Session **********************
         */
        $session_conf = config('session');
        if(!empty($session_conf['enable'])){
            if($session_conf['type'] == 'redis'){
                $handler = new \Sword\Component\Session\RedisSessionHandler($session_conf);
            }elseif($session_conf['type'] == 'file'){
                $handler = new FileSession(EASYSWOOLE_TEMP_DIR . '/Session');
            }
            Session::getInstance($handler);

            Di::getInstance()->set(SysConst::HTTP_GLOBAL_ON_REQUEST, function (Request $request, Response $response) {
                //验证是否浏览器
                if($request->getHeader('user-agent')){
                    $session_conf = config('session');
                    // 获取客户端 Cookie 中 sessionName 参数
                    $cookie = $request->getCookieParams($session_conf['sessionName']);
                    if (!$cookie) {
                        $cookie = Random::character(32); // 生成 sessionId
                        // 设置向客户端响应 Cookie 中 easy_session 参数
                        $response->setCookie($session_conf['sessionName'], $cookie, time() + $session_conf['expire']);
                    }
                    // 存储 sessionId 方便调用，也可以通过其它方式存储
                    $request->withAttribute('sessionId', $cookie);
                    Session::getInstance()->create($cookie);
                }
            });
            Di::getInstance()->set(SysConst::HTTP_GLOBAL_AFTER_REQUEST, function (Request $request, Response $response) {
                //验证是否浏览器
                if($request->getHeader('user-agent')) {
                    //关闭session
                    Session::getInstance()->close($request->getAttribute('sessionId'));
                }
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

    public static function logoSword()
    {
        $sword = Sword::VERSION;
        $s_v = phpversion('swoole');
        $p_v = phpversion();
        $es_v = SysConst::EASYSWOOLE_VERSION;
        $t_d = EASYSWOOLE_TEMP_DIR;
        $l_d = EASYSWOOLE_LOG_DIR;
        echo <<<LOGO
   _____                      _  
  / ____|                    | |  PHP      \e[34mv{$p_v}\e[0m
 | (_____      _____  _ __ __| |  Swoole   \e[34mv{$s_v}\e[0m
  \___ \ \ /\ / / _ \| '__/ _` |  Temp Dir \e[34m{$t_d}\e[0m
  ____) \ V  V | (_) | | | (_| |  Log Dir  \e[34m{$l_d}\e[0m
 |_____/ \_/\_/ \___/|_|  \__,_|  Based \e[32mEasySwoole v{$es_v}\e[0m
 ------------------------v{$sword}------------------------

LOGO;
    }

}
