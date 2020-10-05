<?php

namespace App\Api\Fourth\Controllers;

use App\Api\Foundation\Controllers\Controller;
use App\Services\Activity\PackageService;
use App\Services\Common\AreaService;
use Exception;

/**
 * Class PackageController
 * @package App\Api\Fourth\Controllers
 */
class PackageController extends Controller
{
    /**
     * @var AreaService
     */
    protected $areaService;

    /**
     * @var PackageService
     */
    protected $packageService;

    /**
     * PackageController constructor.
     * @param AreaService $areaService
     * @param PackageService $packageService
     */
    public function __construct(AreaService $areaService, PackageService $packageService)
    {
        $this->areaService = $areaService;
        $this->packageService = $packageService;
    }

    /**
     * @return array
     * @throws Exception
     */
    public function index()
    {
        $condition = [
            'warehouse_id' => $this->warehouse_id,
            'area_id' => $this->area_id,
            'area_city' => $this->area_city,
            'user_id' => $this->uid
        ];

        $result = $this->packageService->getPackageList($condition);

        return $result;
    }
}
