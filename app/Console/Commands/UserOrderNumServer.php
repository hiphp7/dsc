<?php

namespace App\Console\Commands;

use App\Models\UserOrderNum;
use App\Models\Users;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\DscRepository;
use App\Repositories\Common\TimeRepository;
use App\Services\Order\OrderMobileService;
use Illuminate\Console\Command;

class UserOrderNumServer extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:user:order';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'User order select status command';

    protected $timeRepository;
    protected $baseRepository;
    protected $dscRepository;
    public $uid = 0;
    public $seeder = 0;

    public function __construct(
        TimeRepository $timeRepository,
        BaseRepository $baseRepository,
        DscRepository $dscRepository
    )
    {
        parent::__construct();
        $this->timeRepository = $timeRepository;
        $this->baseRepository = $baseRepository;
        $this->dscRepository = $dscRepository;
    }

    /**
     * Execute the console command.
     *
     * @throws \Exception
     */
    public function handle()
    {
        $cache_time = $this->timeRepository->getLocalDate('Y-m-d');

        $page_size = 10;

        $totals = Users::whereHas('getOrder');

        if ($this->uid > 0) {
            $totals = $totals->where('user_id', $this->uid);
        }

        $totals = $totals->count();

        $countpage = ceil($totals / $page_size); #计算总页面数

        $cache_name = 'get_user_handle_' . $cache_time;
        $list = cache($cache_name);
        $list = !is_null($list) ? $list : [];

        if (empty($list) || $this->uid > 0 || $this->seeder == 1) {

            $list = Users::select('user_id', 'user_name')
                ->whereHas('getOrder');

            if ($this->uid > 0) {
                $list = $list->where('user_id', $this->uid);
            }

            $list = $this->baseRepository->getToArrayGet($list);

            if ($this->uid == 0 && $this->seeder == 0) {
                cache()->forever($cache_name, $list);
            }
        }

        $cache_name_file = '';
        if ($this->uid == 0) {
            $cache_name_file = 'user_order_number_cache_' . $cache_time;
            $this->dscRepository->writeStaticCache('', $cache_name_file, $list, 'user_order/');
        }

        if ($list) {

            for ($page = 1; $countpage >= $page; $page++) {

                if ($this->uid == 0) {
                    $list = $this->dscRepository->readStaticCache('', $cache_name_file, 'user_order/');
                }

                $arr = $this->dscRepository->pageArray($page_size, $page, $list);

                if ($arr['list']) {
                    foreach ($arr['list'] as $key => $val) {
                        if ($val) {
                            $user_cache_name = 'user_' . $cache_time . '_' . $val['user_id'];

                            $userTime = cache($user_cache_name);
                            $userTime = !is_null($userTime) ? $userTime : false;

                            if ($userTime === false || $this->uid > 0 || $this->seeder == 1) {

                                $user = app(OrderMobileService::class)->orderNum($val['user_id']);

                                $count = UserOrderNum::where('user_id', $val['user_id'])->count();

                                $other = [
                                    'user_name' => $val['user_name'],
                                    'order_all_num' => $user['all'],
                                    'order_nopay' => $user['nopay'],
                                    'order_nogoods' => $user['nogoods'],
                                    'order_isfinished' => $user['isfinished'],
                                    'order_isdelete' => $user['isdelete'],
                                    'order_team_num' => $user['team_num'],
                                    'order_not_comment' => $user['not_comment'],
                                    'order_return_count' => $user['return_count']
                                ];

                                if ($count > 0) {
                                    UserOrderNum::where('user_id', $val['user_id'])->update($other);
                                } else {
                                    $other['user_id'] = $val['user_id'];
                                    UserOrderNum::insert($other);
                                }

                                if ($this->uid == 0 && $this->seeder == 0) {
                                    cache()->forever($user_cache_name, ['time' => $user_cache_name]);
                                }
                            }

                            /*if ($this->seeder == 1) {
                                if ($other) {
                                    var_dump($other);
                                }
                                var_dump("当前第" . $page . "页" . "，会员ID：" . $val['user_id'] . '，会员名称：' . $val['user_name']);
                            }*/

                            /* 每隔三秒执行循环 */
                            if ($this->uid == 0 && $this->seeder == 0) {
                                sleep(1);
                            }
                        }
                    }
                }
            }
        }
    }

}
