<?php

namespace App\Services\Goods;

use App\Models\RegionWarehouse;
use App\Repositories\Common\BaseRepository;
use App\Services\Merchant\MerchantCommonService;

class GoodsInventoryLogsManageService
{
    protected $merchantCommonService;
    protected $baseRepository;

    public function __construct(
        MerchantCommonService $merchantCommonService,
        BaseRepository $baseRepository

    )
    {
        $this->merchantCommonService = $merchantCommonService;
        $this->baseRepository = $baseRepository;
    }

    public function getInventoryRegion($region_id)
    {
        $region_name = RegionWarehouse::where('region_id', $region_id)->value('region_name');
        $region_name = $region_name ?? '';
        return $region_name;
    }
}
