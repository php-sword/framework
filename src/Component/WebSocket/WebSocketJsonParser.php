<?php declare(strict_types=1);
/**
 * This file is part of Sword.
 * @link     http://sword.kyour.cn
 * @document http://sword.kyour.cn/doc
 * @contact  kyour@vip.qq.com
 * @license  http://github.com/php-sword/sword/blob/master/LICENSE
 */
namespace Sword\Component\WebSocket;

use EasySwoole\Socket\AbstractInterface\ParserInterface;
use EasySwoole\Socket\Client\WebSocket;
use EasySwoole\Socket\Bean\Caller;
use EasySwoole\Socket\Bean\Response;

/**
 * Class WebSocketJsonParser
 *
 * 此类是自定义的 websocket 消息解析器
 * 此处使用的设计是使用 json string 作为消息格式
 * 当客户端消息到达服务端时，会调用 decode 方法进行消息解析
 * 会将 websocket 消息 转成具体的 Class -> Action 调用 并且将参数注入
 *
 * @package App\WebSocket
 */
class WebSocketJsonParser implements ParserInterface
{
    /**
     * decode
     * @param  string         $raw    客户端原始消息
     * @param  WebSocket      $client WebSocket Client 对象
     * @return Caller         Socket  调用对象
     */
    public function decode($raw, $client) : ? Caller
    {
        //解析错误回调方法
        $exception = '\App\WebSocket\EventListener::onMessageException';

        // 解析 客户端原始消息
        $data = json_decode($raw, true);
        if (!is_array($data)) {
            $e = new \Exception('The body is not in JSON format!', 1);
            $exception($e, $raw);
            return null;
        }

        if(!isset($data['cmd'])){
            $e = new \Exception('Parameter "cmd" does not exist!', 2);
            $exception($e, $raw);
            return null;
        }
        $cmd = explode('.', $data['cmd']);

        $ns_len = count($cmd) -1;

        $ns_str = '';
        for($i = 0; $i < $ns_len; $i++){
            $ns_str .= '\\'.ucfirst($cmd[$i]);
        }

        // new 调用者对象
        $caller =  new Caller();
        /**
         * 设置被调用的类 这里会将ws消息中的 class 参数解析为具体想访问的控制器
         * 如果更喜欢 event 方式 可以自定义 event 和具体的类的 map 即可
         * 注 目前 easyswoole 3.0.4 版本及以下 不支持直接传递 class string 可以通过这种方式
         */
        $class = '\\App\\WebSocket'. ($ns_str ?? 'Index');
        // $class = '\\App\\WebSocket\\'. ucfirst($cmd[0] ?? 'Index');

        if(!class_exists($class)){
            $e = new \Exception("Controller class {$class} does not exist!", 3);
            $exception($e, $raw);
            return null;
        }
        $caller->setControllerClass($class);

        // 设置被调用的方法
        $caller->setAction($cmd[$ns_len] ?? 'index');

        // 设置被调用的Args
        $caller->setArgs($data);
        return $caller;
    }

    /**
     * encode
     * @param  Response     $response Socket Response 对象
     * @param  WebSocket    $client   WebSocket Client 对象
     * @return string             发送给客户端的消息
     */
    public function encode(Response $response, $client) : ? string
    {
        /**
         * 这里返回响应给客户端的信息
         * 这里应当只做统一的encode操作 具体的状态等应当由 Controller处理
         */
        return $response->getMessage();
    }
}