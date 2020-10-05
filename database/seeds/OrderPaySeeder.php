<?php

use Illuminate\Database\Seeder;
use App\Console\Commands\OrderPayServer;

class OrderPaySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        app(OrderPayServer::class)->handle();
    }
}