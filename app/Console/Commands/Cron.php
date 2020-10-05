<?php

namespace App\Console\Commands;

use App\Services\Cron\CronArtisanService;
use Illuminate\Console\Command;

class Cron extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:cron {code=all}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'cron manage command';

    /**
     * @var CronArtisanService
     */
    protected $cronArtisanService;

    /**
     * Cron constructor.
     * @param CronArtisanService $cronArtisanService
     */
    public function __construct(CronArtisanService $cronArtisanService)
    {
        parent::__construct();
        $this->cronArtisanService = $cronArtisanService;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        // 计划任务
        $cron_code = $this->argument('code');
        if (in_array($cron_code, ['all', 'delivery', 'ipdel', 'manage', 'messtoseller', 'sms', 'unfreeze', 'drp_log'])) {
            // all 默认所有
            $cron_code = $cron_code == 'all' ? '' : $cron_code;
            $this->cronArtisanService->cronList('', '', $cron_code);
        } else {
            $this->error('Error Arguments');
        }
    }
}
