<?php

use Illuminate\Database\Seeder;
use App\Console\Commands\OrderDeliveryServer;

class OrderDeliverySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        app(OrderDeliveryServer::class)->handle();
    }
}