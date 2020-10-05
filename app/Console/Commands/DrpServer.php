<?php

namespace App\Console\Commands;

use App\Models\Users;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\CommonRepository;
use App\Repositories\Common\TimeRepository;
use App\Services\Drp\DrpConfigService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DrpServer extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:drp {action=children}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Drp command';

    protected $commonRepository;
    protected $timeRepository;
    protected $baseRepository;

    public function __construct(
        CommonRepository $commonRepository,
        TimeRepository $timeRepository,
        BaseRepository $baseRepository
    )
    {
        parent::__construct();
        $this->commonRepository = $commonRepository;
        $this->timeRepository = $timeRepository;
        $this->baseRepository = $baseRepository;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $action = $this->argument('action');

        if (file_exists(MOBILE_DRP)) {
            // 检查客户关系
            if ($action == 'children') {
                $this->checkChildren();
            }

            // 检查分销商权益过期时间
            if ($action == 'check_expiry_time') {
                $this->checkExpiryTime();
            }
        }

    }

    /**
     * 检查客户关系
     */
    protected function checkChildren()
    {
        /**
         * 条件：
         * 1.分销配置，设置客户关系过期时间 drp_config code=children_expiry_days value>0
         * 2.用户存在分销上级，users.drp_parent_id > 0,关联分销商表drp_shop 存在
         * 3.用户绑定时间 + 过期配置 < 当前时间
         */

        $children_expiry_days = app(DrpConfigService::class)->drpConfig('children_expiry_days');
        $children_expiry_days = $children_expiry_days['value'] ?? 0;
        if ($children_expiry_days > 0) {
            $res = Users::with([
                'getParentDrpShop'
            ])
                ->where('drp_parent_id', '>', 0)
                ->where('drp_bind_update_time', '>', 0)
                ->where('drp_bind_update_time', '<', $this->timeRepository->getGmTime() - $children_expiry_days * 86400);
            $user_list = $this->baseRepository->getToArrayGet($res);
            if ($user_list) {
                foreach ($user_list as $row) {
                    //过期 去除父级
                    Users::where('user_id', $row['user_id'])->update([
                        'parent_id' => 0,
                        'drp_parent_id' => 0,
                        'drp_bind_time' => 0,
                        'drp_bind_update_time' => 0
                    ]);
                }
            }
        }
    }

    /**
     * 检查分销商权益过期时间
     */
    protected function checkExpiryTime()
    {
        $now = $this->timeRepository->getGmTime();

        // 未过期的分销商(非永久有效类型)
        DB::table('drp_shop')->where('membership_card_id', '>', 0)->where('membership_status', 1)->where('expiry_time', '>', 0)
            ->chunkById(1000, function ($users) use ($now) {
                foreach ($users as $user) {

                    if ($user->membership_card_id > 0) {
                        $expiry_time = $user->expiry_time;
                        $expiry_type = $user->expiry_type;

                        if (empty($expiry_type)) {
                            $expiry_type = DB::table('user_membership_card')->where('id', $user->membership_card_id)->value('expiry_type');
                        }

                        if (isset($expiry_type) && !empty($expiry_type)) {
                            // 开始与结束时间
                            if ($expiry_type == 'timespan' || $expiry_type == 'days') {
                                // 当前时间 大于 会员权益领取天数记录时间
                                if ($now > $expiry_time) {
                                    // 已过期
                                    DB::table('drp_shop')
                                        ->where('id', $user->id)
                                        ->update(['membership_status' => 0, 'audit' => 0]);
                                }
                            }
                        }

                    } else {
                        continue;
                    }
                }
            });
    }
}
