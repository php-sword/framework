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
    private $redis;
    private $redisPool;
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
        $this->redisPool->recycleObj($this->redis);
        return true;
    }

    /**
     * SESSION打开
     * @param   $savePath   string 保存路径
     * @param float|null $timeout
     * @return  boolean 是否成功
     * @throws \Throwable
     */
    public function open($savePath, ?float $timeout = null): bool
    {
        $this->redisPool = RedisPool::getInstance()->getPool();
        $this->redis= $this->redisPool->getObj();
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
        //空实现
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
            $this->redis->set($sessionId, serialize($data), $this->expire);
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
        $this->redis->del($sessionId);
        return true;
    }

    /**
     * 读取SESSION信息并验证是否有效
     * @param   $session_id string session的key值
     * @return  mixed
     */
    public function read(string $session_id, ?float $timeout = null): ?array
    {
        $data = unserialize($this->redis->get($session_id));
        if(is_array($data)){
            return $data;
        }else{
            return null;
        }
    }

}
