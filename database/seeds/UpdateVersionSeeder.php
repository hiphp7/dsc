<?php

use Illuminate\Database\Seeder;
use App\Repositories\Common\DscRepository;

class UpdateVersionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @throws Exception
     */
    public function run()
    {
        $this->updateVersion();
    }

    public function updateVersion()
    {
        app(DscRepository::class)->getPatch();
    }
}