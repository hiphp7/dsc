<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ClearCacheServer extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:cache {action=clear}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'cache command';

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 缓存操作
     *
     * @throws \Exception
     */
    public function handle()
    {
        $action = $this->argument('action');

        if ($action == 'clear') {
            /* 清除缓存 */

            cache()->forget('shop_config');
            cache()->flush();
        }
    }
}