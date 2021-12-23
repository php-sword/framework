<?php declare(strict_types=1);
/**
 * This file is part of Sword.
 * @link     http://sword.kyour.cn
 * @document http://sword.kyour.cn/doc
 * @contact  kyour@vip.qq.com
 * @license  http://github.com/php-sword/sword/blob/master/LICENSE
 */
namespace Sword\Component\Session;

use EasySwoole\Redis\Redis;
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
     * @param array $config
     */
    public function __construct(array $config = [])
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
    public function open(string $sessionId, ?float $timeout = null): bool
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
        $expire = $this->expire;
        RedisPool::invoke(function (Redis $redis) use($sessionId, $data, $expire) {
            //若无数据写入，则不存储key
            if(!empty($data)){
                $redis->set($sessionId, serialize($data), $expire);
            }
        });
        return true;
    }

    /**
     * 删除Session信息
     * @param   $sessionId string session的key值
     * @return  boolean
     */
    public function destroy(string $sessionId): bool
    {
        RedisPool::invoke(function (Redis $redis) use($sessionId) {
            $redis->del($sessionId);
        });

        return true;
    }

    /**
     * 读取SESSION信息并验证是否有效
     * @param   $sessionId string session的key值
     * @param float|null $timeout
     * @return array|null
     */
    public function read(string $sessionId, ?float $timeout = null): ?array
    {
        $data = null;
        RedisPool::invoke(function (Redis $redis) use($sessionId, &$data) {
            $data = $redis->get($sessionId);
        });

        if($data === null) return null;
        $data = unserialize($data);
        if(is_array($data)){
            return $data;
        }else{
            return null;
        }
    }

}
