<?php

namespace App\Services\UserRights;

use App\Models\UserMembershipRights;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\DscRepository;
use App\Repositories\Common\TimeRepository;


class UserRightsCommonService
{
    protected $config;

    protected $baseRepository;
    protected $timeRepository;
    protected $dscRepository;

    public function __construct(
        BaseRepository $baseRepository,
        TimeRepository $timeRepository,
        DscRepository $dscRepository
    )
    {
        $this->baseRepository = $baseRepository;
        $this->timeRepository = $timeRepository;
        $this->dscRepository = $dscRepository;
    }


    /**
     * 查询信息
     * @param string $code
     * @return array|mixed
     */
    public function userRightsInfo($code = '')
    {
        if (empty($code)) {
            return false;
        }

        $info = UserMembershipRights::query()->where('code', $code)->first();

        $info = $info ? $info->toArray() : [];

        if (!empty($info)) {
            $info['install'] = 1;
            $info['rights_configure'] = empty($info['rights_configure']) ? '' : unserialize($info['rights_configure']);
            $info['icon'] = empty($info['icon']) ? '' : ((stripos($info['icon'], 'assets') !== false) ? asset($info['icon']) : $this->dscRepository->getImagePath($info['icon']));
        }

        return $info;
    }

}