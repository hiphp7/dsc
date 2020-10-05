<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        //
    ];

    /**
     * Define the application's command schedule.
     *
     * @param \Illuminate\Console\Scheduling\Schedule $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        /**
         * 服务器添加：* * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
         */

        // 每小时执行一次任务
        $schedule->command('app:cron')->hourly();

        /* 每小时执行一次，查询已付款订单，改变付款状态为未付款的订单 */
        $schedule->command('app:order:pay')->hourly();

        /* 每天午夜执行一次任务（译者注：每天零点），查询已付款已发货订单，改变未收货的订单 */
        $schedule->command('app:order:delivery')->daily();

        /* 每天午夜执行一次任务（译者注：每天零点），执行为生成缓存的会员数据，更新会员订单相关信息数量的数据 */
        $schedule->command('app:user:order')->daily();

        /* 每天午夜执行一次任务（译者注：每天零点），查询账单订单数据是否存在 */
        $schedule->command('app:commission sorder')->daily();

        /* 每天 1 点 和 3 点分别执行一次任务，执行为生成账单 */
        $schedule->command('app:commission')->twiceDaily(1, 3);

        /* 每天 1 点 和 3 点分别执行一次任务，执行为生成账单详细数据 */
        $schedule->command('app:commission charge')->twiceDaily(1, 3);

        /* 每天午夜执行一次任务（译者注：每天零点），执行账单订单佣金插入数据 */
        $schedule->command('app:commission settlement')->daily();

        /* 每天每两个小时执行一次任务，执行订单失效操作 */
        $schedule->command('app:timeout')->cron('0 */2 * * *');

        /* 每天每6个小时执行一次任务，执行缓存操作[：清除缓存] */
        $schedule->command('app:cache')->cron('0 */6 * * *');

        /* 每天午夜执行一次任务（译者注：每天零点），解除过期的分销客户关系 */
        $schedule->command('app:drp children')->daily();

        /* 每天 2 点 和 8 点分别执行一次任务，执行检查更新分销商权益过期时间 */
        $schedule->command('app:drp check_expiry_time')->twiceDaily(2, 8);

    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
