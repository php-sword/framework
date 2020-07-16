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

use sword\Redis;
// use sethink\swooleOrm\Db;
// use sethink\swooleOrm\MysqlPool;

use EasySwoole\ORM\DbManager;
use EasySwoole\ORM\Db\Connection;
use EasySwoole\ORM\Db\Config;

/**
 * Class App
 *
 * Sword框架app管理
 */
class App
{

    const RUNTIME_POOL = APP_PATH.'/runtime/server.tmp';

    protected $MysqlPool;

    //Sword框架路径
    private $swordPath = '';

    //配置信息
    private $config = [];

    // 构造函数
    function __construct()
    {

        $this->swordPath   = dirname(__DIR__) . DIRECTORY_SEPARATOR;

    }

    //启动app实例
    function run()
    {
        global $argv;

        //判断命令行
        if(isset($argv[1])){
            //启动服务
            if($argv[1] == "start"){

                $this->init($argv);

            //停止服务
            }elseif($argv[1] == "stop"){

                echo "Stopping.";

                $rp = file_get_contents(self::RUNTIME_POOL);
                if($rp == 'stop'){
                    echo "Stopping, cannot repeat operation.";
                }
                file_put_contents(self::RUNTIME_POOL,'stop');
                
                \Swoole\Timer::tick(1000, function(){

                    //监听信道，是否关闭
                    $rp = file_get_contents(self::RUNTIME_POOL);
                    if($rp == ''){
                        echo "\nBye.\n";
                        \Swoole\Timer::clearAll();
                    }
                    echo ".";
                });

            }
        }else{
            echo "Parameter cannot be empty, you can enter（start,stop）\n";
            die;
        }

    }

    //初始化应用
    private function init($argv)
    {
        //载入助手函数
        include_once $this->swordPath . 'helper.php';
        
        
        //载入助手函数
        include_once APP_PATH . '/app/common.php';

        //将app实例存入容器
        app($this);

        //加载配置文件
        config();

        //写入通道文件，表明应用正在运行
        file_put_contents(self::RUNTIME_POOL,'start');

        //取出配置
        $config_app = config('app');

        //swoole服务启动
        $serv = new \Swoole\Server($config_app['host'], $config_app['port']);

        $sw_set = $config_app['swoole_set'];
        $sw_set['daemonize'] = (isset($argv[2]) and $argv[2] == '-d')?1:0;

        $serv->set($sw_set);

        $serv->on("Start", [$this, 'onStart']);

        $serv->on("WorkerStart", [$this, 'onWorkerStart']);

        //监听连接进入事件
        $serv->on('Connect', $config_app['listener']['Connect']??'\\app\\listener\\OnConnect::handle');

        //监听数据接收事件
        $serv->on('Receive', $config_app['listener']['Receive']??'\\app\\listener\\OnReceive::handle');

        //监听连接关闭事件
        $serv->on('Close', $config_app['listener']['Close']??'\\app\\listener\\OnClose::handle');

        //启动服务器
        $serv->start();

    }

    /**
     * 事件函数 onStart
     */
    public function onStart($server)
    {
        echo "Server Started.\n";

        //开启定时任务
        \Swoole\Timer::tick(2000, function() use($server){

            //监听信道，是否关闭
            $rp = file_get_contents(self::RUNTIME_POOL);

            if($rp == 'stop'){
                file_put_contents(self::RUNTIME_POOL,'');
                echo "Bye.\n";
                $server->stop();
            }
        });

    }

    /**
     * 事件函数 onWorkerStart
     */
    public function onWorkerStart($server, $worker_id)
    {

        //连接mysql
        $db_conf = config('database');
    
        $config = new Config();
        $config->setDatabase($db_conf['database']);
        $config->setUser($db_conf['user']);
        $config->setPassword($db_conf['password']);
        $config->setHost($db_conf['host']);

        //连接池配置
        $config->setGetObjectTimeout(3.0); //设置获取连接池对象超时时间
        $config->setIntervalCheckTime(30*1000); //设置检测连接存活执行回收和创建的周期
        $config->setMaxIdleTime(15); //连接池对象最大闲置时间(秒)
        $config->setMaxObjectNum(20); //设置最大连接池存在连接对象数量
        $config->setMinObjectNum(5); //设置最小连接池存在连接对象数量
        $config->setAutoPing(5); //设置自动ping客户端链接的间隔

        DbManager::getInstance()->addConnection(new Connection($config));

        //redis连接
        $redis_conf = config('redis');
        $conf = [
            'RA' => [$redis_conf['host'],$redis_conf['port']]   //定义Redis配置
        ];

        Redis::addServer($conf); //添加Redis配置

        // Redis::set('user','private');
        // echo Redis::get('user');

    }
}
