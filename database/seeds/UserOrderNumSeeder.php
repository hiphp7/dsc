<?php

use Illuminate\Database\Seeder;
use App\Console\Commands\UserOrderNumServer;

class UserOrderNumSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $UserOrderNumServer = app(UserOrderNumServer::class);
        $UserOrderNumServer->seeder = 1;
        $UserOrderNumServer->handle();
    }
}