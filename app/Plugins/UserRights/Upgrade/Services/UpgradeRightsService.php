<?php

namespace App\Plugins\UserRights\Upgrade\Services;

use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\DscRepository;
use App\Repositories\Common\TimeRepository;
use App\Services\UserRights\UserRightsService;


class UpgradeRightsService
{
    protected $baseRepository;
    protected $timeRepository;
    protected $dscRepository;
    protected $userRightsService;

    public function __construct(
        BaseRepository $baseRepository,
        TimeRepository $timeRepository,
        DscRepository $dscRepository,
        UserRightsService $userRightsService
    )
    {
        $this->baseRepository = $baseRepository;
        $this->timeRepository = $timeRepository;
        $this->dscRepository = $dscRepository;
        $this->userRightsService = $userRightsService;
    }


}