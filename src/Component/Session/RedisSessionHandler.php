<?php declare(strict_types=1);
/**
 * This file is part of Sword.
 * @link     http://sword.kyour.cn
 * @document http://sword.kyour.cn/doc
 * @contact  kyour@vip.qq.com
 * @license  http://github.com/php-sword/sword/blob/master/LICENSE
 */
namespace Sword\Component\Session;

use EasySwoole\RedisPool\RedisPool;
use EasySwoole\Session\SessionHandlerInterface;

/**
 * 实现Redis Session
 * @authar kyour.cn
 */
class RedisSessionHandler implements SessionHandlerInterface
{
    private $expire = 86400 * 7; //7天

    /**
     * SessionRedisHandler constructor.
     * @param $config
     */
    public function __construct($config = [])
    {
        isset($config['expire']) && $this->expire = $config['expire'];
    }

    /**
     * SESSION关闭
     * @param string $sessionId
     * @param float|null $timeout
     * @return  boolean
     */
    public function close(string $sessionId, ?float $timeout = null): bool
    {
        return true;
    }

    /**
     * SESSION打开
     * @param string $sessionId
     * @param float|null $timeout
     * @return  boolean 是否成功
     * @throws \Throwable
     */
    public function open($sessionId, ?float $timeout = null): bool
    {
        return true;
    }

    /**
     * 回收超时SESSION信息
     * @param int $expire
     * @param float|null $timeout
     * @return boolean
     */
    public function gc(int $expire, ?float $timeout = null): bool
    {
        return true;
    }

    /**
     * 写入SESSION信息
     * @param string $sessionId
     * @param array $data
     * @param float|null $timeout
     * @return  boolean
     */
    public function write(string $sessionId, array $data, ?float $timeout = null): bool
    {
        //Session 空数据拒绝写入
        if(!empty($data)){
            $redisCluster = RedisPool::defer();
            $redisCluster->set($sessionId, serialize($data), $this->expire);
        }
        return true;
    }

    /**
     * 删除Session信息
     * @param   $sessionId string Session的key值
     * @return  boolean
     */
    public function destroy($sessionId): bool
    {
        $redisCluster = RedisPool::defer();
        $redisCluster->del($sessionId);
        return true;
    }

    /**
     * 读取SESSION信息并验证是否有效
     * @param   $sessionId string session的key值
     * @return  mixed
     */
    public function read(string $sessionId, ?float $timeout = null): ?array
    {
        $redisCluster = RedisPool::defer();

        $data = $redisCluster->get($sessionId);
        if($data === null) return null;
        $data = unserialize($data);
        if(is_array($data)){
            return $data;
        }else{
            return null;
        }
    }

}
