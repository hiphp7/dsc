<?php

namespace App\Extensions;

use App\Models\ImService;
use GuzzleHttp\Client;

/**
 * Class WorkerEvent
 * @package App\Extensions
 */
class WorkerEvent
{
    /**
     * @var Client|null
     */
    private $client = null;

    /**
     * @var string
     */
    private $port = '';

    /**
     * @var string
     */
    private $root_path = '';

    /**
     * @var string
     */
    private $listen_route = '';

    /**
     * @var string
     */
    private $listen_ip = '0.0.0.0';

    /**
     * @var
     */
    private $context;

    /**
     * @var int
     */
    public $timer_expire;

    /**
     * @var bool
     */
    public $is_ssl = false;

    /**
     * @var array
     */
    public $serviceContainer = [];

    /**
     * @var array
     */
    public $customerContainer = [];

    /**
     * @var null
     */
    public $eventContainer = null;

    /**
     * WorkerEvent constructor.
     */
    public function __construct()
    {
        $c = config('chat');
        if (!isset($c['listen_route']) || empty($c['listen_route'])) {
            die(' listen_route need to be configured ');
        }
        if (!isset($c['root_path']) || empty($c['root_path'])) {
            die(' root_path need to be configured ');
        }
        if (!isset($c['server_port']) || empty($c['server_port'])) {
            die('server port need to be configured ');
        }

        $this->root_path = rtrim($c['root_path'], '/') . '/';
        $this->port = $c['server_port']; // 服务端启动监听端口
        if (isset($c['listen_route']) && !empty($c['listen_route'])) {
            $this->listen_route = $c['listen_route'];
        }
        if (isset($c['listen_ip']) && !empty($c['listen_ip'])) {
            $this->listen_ip = $c['listen_ip'];
        }
        if (stripos($this->root_path, 'https') === 0) {
            $this->is_ssl = true;
        }
        if ($this->is_ssl) {
            $this->setpem($c['local_cert'], $c['local_pk']);
        }
        $this->timer_expire = 10;//定时结束会话时间
        unset($c);

        $this->client = new Client();
    }

    /**
     * 修改客服状态
     * 系统关闭   将客服状态改为  未登录
     */
    public function changeServiceStatus()
    {
        ImService::whereRaw('1=1')->update(['chat_status' => 0]);
    }

    /**
     * 设置端口
     * @param null $port
     */
    public function setPort($port = null)
    {
        if (!is_null($port)) {
            $this->port = $port;
        }
    }

    /**
     * 获取端口
     * @return string
     */
    public function getPort()
    {
        return $this->port;
    }

    /**
     * 获取域名
     * @return string
     */
    public function getListenRoute()
    {
        return $this->listen_route;
    }

    /**
     * 获取IP
     * @return string
     */
    public function getListenIp()
    {
        return $this->listen_ip;
    }

    /**
     * 链接数据库
     * @param $data
     * @param $path
     * @return mixed
     */
    public function db($data, $path)
    {
        $res = $this->client->post($path, ['form_params' => $data]);

        return $res->getBody()->getContents();
    }

    /**
     * @param $id
     */
    public function checkUser($id)
    {
        $this->db(['id' => $id], $this->dbconnPath);
    }

    /**
     * @param $id
     * @param $status
     */
    public function customerLogin($id, $status)
    {
        $url = route('kefu.admin.change_login');
        $this->db(['id' => $id, 'status' => $status], $url);
    }

    /**
     * 发送消息并保存
     * @param $connection
     * @param $data
     * @param string $type
     */
    public function sendmsg($connection, $data, $type = '')
    {
        $connection->send(json_encode($data));
        $url = route('kefu.admin.storage_message');
        $data['user_type'] = $connection->userType;
        $data['to_id'] = $connection->uid;
        $this->db($data, $url);
    }

    /**
     * 保存消息
     * @param $data
     */
    public function savemsg($data)
    {
        $url = route('kefu.admin.storage_message');
        $data['to_id'] = empty($data['to_id']) ? 0 : $data['to_id'];
        $this->db($data, $url);
    }

    /**
     * 发送消息
     * @param $connection
     * @param $data
     * @param string $type
     */
    public function sendinfo($connection, $data, $type = '')
    {
        $connection->send(json_encode($data));
    }

    /**
     * 更新接入的消息
     * @param $data
     */
    public function changemsginfo($data)
    {
        $url = route('kefu.admin.change_msg_info');

        $this->db($data, $url);
    }

    /**
     * 获取接入回复
     * @param $data
     * @return mixed
     */
    public function getreply($data)
    {
        $url = route('kefu.admin.getreply');

        return $this->db($data, $url);
    }

    /**
     * 获取证书路径
     * @return mixed
     */
    public function getcontext()
    {
        return $this->context;
    }

    /**
     * 定时发起关闭会话
     * @return mixed
     */
    public function closeolddialog()
    {
        $url = route('kefu.admin.close_old_dialog');

        return $this->db(['close_all_dialog' => 'close_all_dialog'], $url);
    }

    /**
     * 设置证书路径
     * @param $cert
     * @param $pk
     */
    public function setpem($cert, $pk)
    {
        $this->context = [
            'ssl' => [
                // 使用绝对路径
                'local_cert' => $cert, // 也可以是crt文件
                'local_pk' => $pk,
                'verify_peer' => false,
            ]
        ];
    }
}
