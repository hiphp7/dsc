<?php

namespace App\Services\Role;

use App\Models\Role;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\TimeRepository;
use App\Services\Merchant\MerchantCommonService;

class RoleManageService
{

    protected $baseRepository;
    protected $timeRepository;
    protected $merchantCommonService;

    public function __construct(
        BaseRepository $baseRepository,
        TimeRepository $timeRepository,
        MerchantCommonService $merchantCommonService
    )
    {

        $this->baseRepository = $baseRepository;
        $this->timeRepository = $timeRepository;
        $this->merchantCommonService = $merchantCommonService;

    }

    /* 获取角色列表 */
    public function getRoleList()
    {
        $list = [];
        $res = Role::orderBy('role_id', 'DESC');
        $list = $this->baseRepository->getToArrayGet($res);

        return $list;
    }
}
