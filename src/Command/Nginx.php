<?php declare(strict_types=1);
/**
 * This file is part of Sword.
 * @link     http://sword.kyour.cn
 * @document http://sword.kyour.cn/doc
 * @contact  kyour@vip.qq.com
 * @license  http://github.com/php-sword/sword/blob/master/LICENSE
 */
namespace Sword\Command;

use EasySwoole\Command\AbstractInterface\CommandInterface;
use EasySwoole\Command\CommandManager;

class Nginx implements CommandInterface
{
    public function commandName(): string
    {
        return 'nginx';
    }

    public function desc(): string
    {
        return '快速创建Nginx反向代理的配置文件';
    }

    public function exec(): string
    {
//        $manager = CommandManager::getInstance();
        /** 获取原始未变化的argv */
//        var_dump($manager->getOriginArgv());

        $config = [
            //http映射端口 -外网
            'http_port' => 80,
            //https映射端口 -外网
            'https_port' => 443,
            //内网服务端口 与dev.php中配置的端口一直
            'server_port' => 8108,
            //应用根目录 绝对路径，以/结尾
            'root_path' => EASYSWOOLE_ROOT.'/',
            //静态首页 -开启后直接由nginx响应 /index
            'static_index' => false,
            'ws_url' => '',

            //https的ssl证书文件（绝对路径） -仅开启https有效
            'ssl_cer' => '/usr/local/nginx/conf/ssl/xy.kyour.cn/fullchain.cer',
            //https的ssl密钥文件（绝对路径） -仅开启https有效
            'ssl_key' => '/usr/local/nginx/conf/ssl/xy.kyour.cn/xy.kyour.cn.key',

            //图片缓存时间
            'img_cache' => '3d',
            //资源文件缓存时间 （js、css、字体）
            'res_cache' => '7d'
        ];

        echo "\033[36m欢迎使用Sword，请根据下方提示输入，以帮助生成Nginx配置文件！\033[0m\n";

        echo "> \033[32m选择类型：\033[0m\n 1: http (默认)\n 2: https\n 3: http + https\n\033[32m请输入：";
        $in = trim(fgets(STDIN));
        if(!in_array($in, ['1','2','3'])){
            $in = 1;
        }
        $config['type'] = (int) $in;
        echo "选择为{$in}\033[0m\n";

        $_in = config('app.server_port');
        echo "> \033[32m请输入内网服务端口(默认[{$_in}])：\033[0m";
        $in = trim(fgets(STDIN));
        if(!is_numeric($in)){
            $in = $_in;
        }
        $config['server_port'] = (int) $in;
        echo "端口：{$in}\n";

        if(in_array($config['type'], [1,3])){
            echo "> \033[32m请输入Http外网端口(默认80):\033[0m";
            $in = trim(fgets(STDIN));
            if(is_numeric($in)){
                $config['http_port'] = (int) $in;
            }
            echo "端口：{$config['http_port']}\n";
        }
        if(in_array($config['type'], [2,3])){
            echo "> \033[32m请输入Https外网端口(默认443):\033[0m";
            $in = trim(fgets(STDIN));
            if(is_numeric($in)){
                $config['https_port'] = (int) $in;
            }
            echo "端口：{$config['https_port']}\n";

            echo "> \033[32m请输入https的ssl证书文件（绝对路径）:\033[0m";
            $in = trim(fgets(STDIN));
            if($in){
                $config['ssl_cer'] = (int) $in;
            }
            echo "证书：{$in}\n";

            echo "> \033[32m请输入https的ssl密钥文件（绝对路径）:\033[0m";
            $in = trim(fgets(STDIN));
            if($in){
                $config['ssl_key'] = (int) $in;
            }
            echo "密钥：{$in}\n";
        }

        while (true){
            echo "> \033[32m请输入外网访问域名，多个用空格分割：\033[0m";
            $in = trim(fgets(STDIN));
            if($in){
                $config['host_name'] = (int) $in;
                echo "域名：{$in}\n";
                break;
            }
            echo "\033[31m域名不能留空\033[0m\n";
        }

        echo "> \033[32m静态资源目录(默认 Public)：\033[0m";
        $in = trim(fgets(STDIN));
        if(!$in){
            $in = 'Public';
        }
        echo "目录：{$in}\n";
        $config['public_path'] = (int) $in;

        echo "> \033[32m是否开启静态首页，开启后直接由nginx响应首页(index.html)\033[0m\n y: 是\n n: 否(默认)\n\033[32m请输入：\033[0m";
        $in = trim(fgets(STDIN));
        if($in == 'y'){
            $config['static_index'] = true;
        }

        echo "> \033[32mWebsocket的uri,为空则不启用,不支持填写'/'，例如'/ws'：\033[0m";
        $in = trim(fgets(STDIN));
        if($in){
            $config['ws_url'] = $in;
        }
        echo "Websocket：{$in}\n";

        echo "\033[36m配置完成，回车立即生成配置，Ctrl+C退出！\033[0m\n";
        $in = trim(fgets(STDIN));

        $this->buildConfFile($config);

        return '';
    }

    public function help(\EasySwoole\Command\AbstractInterface\CommandHelpInterface $commandHelp): \EasySwoole\Command\AbstractInterface\CommandHelpInterface
    {
        $commandHelp->addAction('test','测试方法');
        $commandHelp->addActionOpt('-no','不输出详细信息');
        return $commandHelp;
    }

    //生成配置文件
    public function buildConfFile($config)
    {

        $str_local = '
    location / {
        proxy_http_version 1.1;
        proxy_set_header Connection "keep-alive";
        proxy_set_header Host $http_host;
        proxy_set_header X-Real-IP $remote_addr;

        '.
            ($config['static_index']?'#接管静态主页
        if ($uri = "/"){
            rewrite ^(.*)$ /index last;
        }':'')
            .'
        #html文件存在 - 重写路径 .html
        if (-f "${request_filename}.html") {
            rewrite ^(.*)$ /$1.html break;
        }
        #代理swoole -没有静态文件的情况下
        if (!-f $request_filename){
			proxy_pass http://127.0.0.1:8108;
        }
    }
';

        $str_http = '
server
{
    listen '.$config['http_port'].';
    #listen [::]:80;
    server_name '.$config['host_name'].';
    root '.$config['root_path'].$config['public_path'].';

    location ~ .*\.(gif|jpg|png|bmp|ico)$
    {
        expires      '.$config['img_cache'].';
    }

    location ~ .*\.(js|css|ttf)?$
    {
        expires      '.$config['res_cache'].';
    }

    location ~ .*\.(html|htm)$ {
        expires      30s;
        #禁止缓存，每次都从服务器请求
        #add_header Cache-Control no-store;
    }
    '.
            //判断是否存在ws_url
            ($config['ws_url']?'
    # Websocket支持
    location '.$config['ws_url'].' {
        proxy_http_version 1.1;
        proxy_set_header Host $http_host;
        proxy_set_header X-Real-IP $remote_addr;

        proxy_set_header Upgrade $http_upgrade;   # 升级协议头
        proxy_set_header Connection upgrade;
        proxy_pass http://127.0.0.1:'.$config['server_port'].';
    }
    ':'').
            $str_local.
            '    access_log off;
}
';

        $str_https = '
# Https配置，其他配置与上面相同，只是多了ssl证书配置
server
{
    listen '.$config['https_port'].' ssl http2;
    #listen [::]:443 ssl http2;
    server_name '.$config['host_name'].';
    root '.$config['root_path'].$config['public_path'].';

    ssl_certificate '.$config['ssl_cer'].';
    ssl_certificate_key '.$config['ssl_key'].';
    ssl_session_timeout 5m;
    ssl_protocols TLSv1 TLSv1.1 TLSv1.2 TLSv1.3;
    ssl_prefer_server_ciphers on;
    ssl_ciphers "TLS13-AES-256-GCM-SHA384:TLS13-CHACHA20-POLY1305-SHA256:TLS13-AES-128-GCM-SHA256:TLS13-AES-128-CCM-8-SHA256:TLS13-AES-128-CCM-SHA256:EECDH+CHACHA20:EECDH+CHACHA20-draft:EECDH+AES128:RSA+AES128:EECDH+AES256:RSA+AES256:EECDH+3DES:RSA+3DES:!MD5";
    ssl_session_cache builtin:1000 shared:SSL:10m;

    location ~ .*\.(gif|jpg|png|bmp|ico)$
    {
        expires      '.$config['img_cache'].';
    }

    location ~ .*\.(js|css|ttf)?$
    {
        expires      '.$config['res_cache'].';
    }

    location ~ .*\.(html|htm)$ {
        expires      30s;
        #禁止缓存，每次都从服务器请求
        #add_header Cache-Control no-store;
    }
    '.
            //判断是否存在ws_url
            ($config['ws_url']?'
    # Websocket支持
    location '.$config['ws_url'].' {
        proxy_http_version 1.1;
        proxy_set_header Host $http_host;
        proxy_set_header X-Real-IP $remote_addr;

        proxy_set_header Upgrade $http_upgrade;   # 升级协议头
        proxy_set_header Connection upgrade;
        proxy_pass http://127.0.0.1:'.$config['server_port'].';
    }
    ':'').
            $str_local.
            '    access_log off;
}
';

        $conf_val = '';
        if($config['type'] == 1){
            $conf_val = $str_http;
        }elseif($config['type'] == 2){
            $conf_val = $str_https;
        }else{
            $conf_val = $str_http . $str_https;
        }
        file_put_contents(EASYSWOOLE_ROOT. "/nginx.conf",$conf_val);
        $path = EASYSWOOLE_ROOT;
        echo "Output successful.\nPath: {$path}/\nFile: nginx.conf\n";

    }
}