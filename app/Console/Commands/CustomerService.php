<?php

namespace App\Console\Commands;

use App\Extensions\WorkerEvent;
use Illuminate\Console\Command;
use Workerman\Lib\Timer;
use Workerman\Worker;

class CustomerService extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:chat {action=start} {--d}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Customer Service Command';

    /**
     * @var WorkerEvent
     */
    protected $workerEvent;

    /**
     * CustomerService constructor.
     * @param WorkerEvent $workerEvent
     */
    public function __construct(WorkerEvent $workerEvent)
    {
        parent::__construct();
        $this->workerEvent = $workerEvent;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        /**
         * workerman 需要带参数 所以得强制修改
         */
        $action = $this->argument('action');
        if (!in_array($action, ['start', 'stop', 'restart', 'reload', 'status'])) {
            $this->error('Error Arguments');
            exit(0);
        }

        global $argv;
        $argv[0] = 'app:chat';
        $argv[1] = $action;
        $argv[2] = $this->option('d') ? '-d' : '';

        /**
         * 创建一个Worker监听一个端口，使用websocket协议通讯
         */
        if ($this->workerEvent->is_ssl) {
            $worker = new Worker("websocket://" . $this->workerEvent->getListenIp() . ":" . $this->workerEvent->getPort(), $this->workerEvent->getcontext());
            $worker->transport = 'ssl';
        } else {
            $worker = new Worker("websocket://" . $this->workerEvent->getListenIp() . ":" . $this->workerEvent->getPort());
        }

        /**
         * 启动4个进程对外提供服务
         */
        $worker->count = 1; // 正常为CPU核心的2倍，如何设置进程数：http://doc.workerman.net/315230

        // 心跳间隔 单位 秒
        define('HEARTBEAT_TIME', 55);  // 定义一个心跳间隔55秒
        define('CHECK_HEARTBEAT_TIME', 1); // 检查连接的间隔时间

        /**
         * 停止服务 将客服状态改为下线
         */
        if ($action == 'stop' || $action == 'restart') {
            echo "change service status...\n";
            $this->workerEvent->changeServiceStatus();
            echo "all service in logout status\n";
        }

        $worker->onConnect = function ($connection) {
            echo "new connection from ip " . $connection->getRemoteIp() . ", client_id " . $connection->id . "\n";
        };

        /**
         * 接受客户端数据  并做处理
         * @param $connection
         * @param $data
         */
        $worker->onMessage = function ($connection, $data) use ($worker) {
            $data = json_decode($data, true);
            $data['store_id'] = isset($data['store_id']) ? intval($data['store_id']) : 0;
            $data['goods_id'] = isset($data['goods_id']) ? intval($data['goods_id']) : 0;

            // 给connection临时设置一个lastMessageTime属性，用来记录上次收到消息的时间
            $connection->lastMessageTime = time();

            $type = $data['type'];
            switch ($type) {
                // 客户端回应服务端的心跳
                case 'ping':
                    $connection->send(json_encode(['type' => 'pong']));
                    break;
                case 'login':
                    if (!isset($data['uid'])) {
                        break;
                    }
                    /**
                     * 用户登录  保存用户信息
                     */
                    $connection->uid = $data['uid'];
                    $connection->uname = $data['name'];
                    $data['user_type'] = (isset($data['user_type']) && $data['user_type'] == 'service') ? 'service' : 'customer';
                    $connection->userType = $data['user_type'];// 客服或者客户
                    $connection->avatar = $data['avatar'];// 头像
                    $connection->origin = $data['origin'];// 来源   PC | 手机
                    if ($data['user_type'] == 'service') {
                        $connection->store_id = $data['store_id'];//商家ID

                        $this->workerEvent->serviceContainer[$data['store_id']][$data['uid']] = $connection;

                        /** 验证用户   修改客服登录状态 */
                        $this->workerEvent->customerLogin($data['uid'], 1);
                    } elseif ($data['user_type'] == 'customer') {
                        /** 如果用户存在、剔除 */
                        if (isset($this->workerEvent->customerContainer[$data['uid']])) {
                            $msg = ['message_type' => 'others_login'];
                            $this->workerEvent->sendinfo($this->workerEvent->customerContainer[$data['uid']], $msg);
                        }
                        $connection->targetService = [
                            'store_id' => $data['store_id']
                        ];
                        $this->workerEvent->customerContainer[$data['uid']] = $connection;
                    }
                    $connection->send(json_encode(['msg' => 'yes', 'message_type' => 'init']));

                    break;
                case 'sendmsg':
                    /**
                     * 发送消息
                     */
                    $data['msg'] = escapeHtml($data['msg']);
                    $msg = [
                        'from_id' => $connection->uid,
                        'name' => $connection->uname,
                        'time' => date('H:i:s'),
                        'message' => $data['msg'],
                        'avatar' => $data['avatar'],
                        'goods_id' => $data['goods_id'],
                        'store_id' => $data['store_id'],
                        'message_type' => 'come_msg',
                        'origin' => $data['origin'],
                        'user_type' => $connection->userType,
                    ];

                    //客户发送给客服
                    if ($connection->userType == 'customer') {
                        if (empty($data['to_id'])) {
                            //群发
                            $msg['user_type'] = 'service';
                            $msg['origin'] = $data['origin'];
                            $msg['status'] = 1;
                            $this->workerEvent->savemsg($msg);
                            $msg['user_type'] = 'customer';
                            $msg['message_type'] = 'come_wait';
                            if (isset($this->workerEvent->serviceContainer[$data['store_id']])) {
                                foreach ($this->workerEvent->serviceContainer[$data['store_id']] as $uid => $con) {
                                    $this->workerEvent->sendinfo($con, $msg);
                                }
                            }
                        } else {
                            //直接发送
                            $msg['to_id'] = $data['to_id'];
                            if (isset($this->workerEvent->serviceContainer[$data['store_id']][$data['to_id']])) {
                                $msg['status'] = 0;
                                $this->workerEvent->sendmsg($this->workerEvent->serviceContainer[$data['store_id']][$data['to_id']], $msg);
                                $connection->targetService = [
                                    'store_id' => $data['store_id'],
                                    'sid' => $data['to_id']
                                ];
                            } elseif (!isset($this->workerEvent->serviceContainer[$data['store_id']][$data['to_id']])) {
                                //保存
                                $msg['user_type'] = 'service';
                                $msg['status'] = 1;
                                $this->workerEvent->savemsg($msg);
                            }
                        }
                    } elseif ($connection->userType == 'service') {
                        if (empty($data['to_id']) || !isset($this->workerEvent->customerContainer[$data['to_id']])) {
                            //用户不在  保存消息
                            $msg['to_id'] = $data['to_id'];
                            $msg['status'] = 1;
                            $this->workerEvent->savemsg($msg);
                            break;
                        }

                        if ($this->workerEvent->customerContainer[$data['to_id']]->targetService['store_id'] == $data['store_id']
                            && (
                                !isset($this->workerEvent->customerContainer[$data['to_id']]->targetService['sid'])
                                || $this->workerEvent->customerContainer[$data['to_id']]->targetService['sid'] == ''
                            )
                        ) {
                            // 当前没在聊天
                            // 设置客户当前聊天对象
                            $this->workerEvent->customerContainer[$data['to_id']]->targetService = [
                                'store_id' => $data['store_id'],
                                'sid' => $connection->uid
                            ];
                            $msg['status'] = 0;
                            $this->workerEvent->sendmsg($this->workerEvent->customerContainer[$data['to_id']], $msg);
                        } // 判断客户当前 是否正在聊天
                        elseif ($this->workerEvent->customerContainer[$data['to_id']]->targetService['store_id'] == $data['store_id']
                            && $this->workerEvent->customerContainer[$data['to_id']]->targetService['sid'] == $connection->uid
                        ) {
                            // 客户正在与本人聊天
                            $msg['status'] = 0;
                            $this->workerEvent->sendmsg($this->workerEvent->customerContainer[$data['to_id']], $msg);
                        } else {
                            // 客户在跟别人聊天
                            if ($this->workerEvent->customerContainer[$data['to_id']]->origin == 'H5') {
                                // 手机登录 存为离线消息
                                $msg['to_id'] = $data['to_id'];
                                $msg['status'] = 1;
                                $this->workerEvent->savemsg($msg);
                            } else {
                                // PC登录 直接发送
                                $this->workerEvent->sendmsg($this->workerEvent->customerContainer[$data['to_id']], $msg);
                            }
                        }
                    }
                    break;
                case 'info':
                    /**
                     * 通知所有客服消息被抢   ser_id为客服ID   cus_id为客户ID
                     */
                    $msg = ['cus_id' => $data['msg'], 'ser_id' => $data['from_id'], 'message_type' => 'robbed', 'goods_id' => $data['goods_id'], 'store_id' => $data['store_id']];
                    if (isset($this->workerEvent->serviceContainer[$data['store_id']])) {
                        foreach ($this->workerEvent->serviceContainer[$data['store_id']] as $uid => $con) {
                            if ($con->uid == $data['from_id']) {
                                continue;
                            }
                            $this->workerEvent->sendinfo($con, $msg);
                        }
                    }
                    $this->workerEvent->changemsginfo($msg);
                    //用户存在则通知用户已被接入
                    $msg = ['service_id' => $data['from_id'], 'name' => $connection->uname, 'store_id' => $data['store_id'], 'message_type' => 'user_robbed'];
                    if (isset($this->workerEvent->customerContainer[$data['msg']])) {
                        $msg['msg'] = $this->workerEvent->getreply(['service_id' => $data['from_id']]);
                        $msg['avatar'] = $this->workerEvent->serviceContainer[$data['store_id']][$data['from_id']]->avatar;
                        $this->workerEvent->sendinfo($this->workerEvent->customerContainer[$data['msg']], $msg);

                        // 设置客户当前聊天对象
                        $this->workerEvent->customerContainer[$data['msg']]->targetService = [
                            'store_id' => $data['store_id'],
                            'sid' => $data['from_id']
                        ];
                    }
                    break;
                case 'change_service':
                    /**
                     * 切换客服
                     * $data['type'];
                     * $data['to_id'];  客服ID
                     * $data['from_id'];  客服ID
                     * $data['goods_id'];
                     * $data['store_id'];
                     * $data['cus_id'];   客户ID
                     */
                    if ($connection->userType == 'service') {
                        if (
                            isset($this->workerEvent->customerContainer[$data['cus_id']]) &&
                            isset($this->workerEvent->serviceContainer[$data['store_id']][$data['from_id']])
                        ) {
                            $this->workerEvent->customerContainer[$data['cus_id']]->targetService = [
                                'store_id' => $data['store_id'],
                                'sid' => $data['to_id']
                            ];
                            //通知客户
                            $msg = ['sid' => $data['to_id'], 'fid' => $data['from_id'], 'store_id' => $data['store_id'],
                                'message_type' => 'change_service'];
                            $this->workerEvent->sendinfo($this->workerEvent->customerContainer[$data['cus_id']], $msg);
                            //通知客服本人
                            $msg = ['sid' => $data['to_id'], 'fid' => $data['from_id'], 'cus_id' => $data['cus_id'], 'message_type' => 'change_service'];
                            $this->workerEvent->sendinfo($connection, $msg);
                            //通知客服
                            $msg = ['sid' => $data['to_id'], 'fid' => $data['from_id'], 'cus_id' => $data['cus_id'], 'store_id' => $data['store_id'], 'message_type' => 'change_service'];
                            $this->workerEvent->sendinfo($this->workerEvent->serviceContainer[$data['store_id']][$data['to_id']], $msg);
                        }
                    }
                    break;
                case 'close_link':
                    /**
                     * 通知用户  客服已断开
                     */
                    $msg = ['to_id' => $data['to_id'], 'msg' => '客服已断开', 'message_type' => 'close_link'];
                    //用户存在则通知用户已被接入
                    if (isset($this->workerEvent->customerContainer[$data['to_id']])) {
                        $this->workerEvent->sendinfo($this->workerEvent->customerContainer[$data['to_id']], $msg);
                    }
                    break;
            }
        };

        // 进程启动后设置一个每秒运行一次的定时器
        $worker->onWorkerStart = function ($worker) {
            Timer::add(CHECK_HEARTBEAT_TIME, function () use ($worker) {
                $time_now = time();
                foreach ($worker->connections as $connection) {
                    // 有可能该connection还没收到过消息，则lastMessageTime设置为当前时间
                    if (empty($connection->lastMessageTime)) {
                        $connection->lastMessageTime = $time_now;
                        continue;
                    }
                    // 上次通讯时间间隔大于心跳间隔，则认为客户端已经下线，关闭连接
                    if ($time_now - $connection->lastMessageTime > HEARTBEAT_TIME) {
                        $connection->close();
                    }
                }
            });
        };

        /**
         * 当客户端断开链接时
         * @param $connection
         */
        $worker->onClose = function ($connection) {
//            if ($connection->userType == 'service') {
//                unset($this->workerEvent->serviceContainer[$connection->store_id][$connection->uid]);
//            } elseif ($connection->userType == 'customer') {
//                $msg = ['message_type' => 'others_login'];
//                if (isset($this->workerEvent->customerContainer[$connection->uid])) {
//                    $this->workerEvent->sendinfo($this->workerEvent->customerContainer[$connection->uid], $msg);
//                }
//                unset($this->workerEvent->customerContainer[$connection->uid]);
//            }
//            $this->workerEvent->customerLogin($connection->uid, 0);
//
//            /**
//             * 通知好友用户登出
//             */
//            foreach ($connection->worker->connections as $con) {
//                $user = ['uid' => $connection->uid, 'message_type' => 'leave'];
//                $this->workerEvent->sendmsg($con, $user);
//            }
        };

        /**
         * 关闭客服   执行操作
         * 修改所有客服状态为  未登录
         */
        $worker->onWorkerStop = function () {
            echo "change service status...\n";
            $this->workerEvent->changeServiceStatus();
            echo "all service in logout status\n";
        };

        // 运行worker
        Worker::runAll();
    }
}
