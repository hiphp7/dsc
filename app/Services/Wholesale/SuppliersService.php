<?php

namespace App\Services\Wholesale;


use App\Models\Region;
use App\Models\Suppliers;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\DscRepository;
use App\Repositories\Common\TimeRepository;


class SuppliersService
{
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
     * 供应商信息
     * @param int $user_id
     * @return array
     */
    public function suppliersInfo($user_id = 0)
    {
        if (empty($user_id)) {
            return [];
        }

        $model = Suppliers::where('user_id', $user_id)->first();

        $result = $model ? $model->toArray() : [];
        if (!empty($result)) {

            $result['front_of_id_card'] = $this->dscRepository->getImagePath($result['front_of_id_card']);
            $result['reverse_of_id_card'] = $this->dscRepository->getImagePath($result['reverse_of_id_card']);
            $result['suppliers_logo'] = $this->dscRepository->getImagePath($result['suppliers_logo']);
        }

        return $result;
    }

    /**
     * 更新
     * @param int $user_id
     * @param array $data
     * @return array
     */
    public function updateSuppliers($user_id = 0, $data = [])
    {
        if (empty($user_id) || empty($data)) {
            return [];
        }

        $data['review_status'] = 1; // 修改须重新审核

        $data = $this->baseRepository->getArrayfilterTable($data, 'suppliers');

        // 将数组null值转为空
        array_walk_recursive($data, function (&$val, $key) {
            $val = ($val === null) ? '' : $val;
        });

        return Suppliers::where('user_id', $user_id)->update($data);
    }

    /**
     * 新增
     * @param array $data
     * @return array
     */
    public function createSuppliers($data = [])
    {
        if (empty($data)) {
            return [];
        }

        $data['add_time'] = $this->timeRepository->getGmTime();
        $data['review_status'] = 1;

        $data = $this->baseRepository->getArrayfilterTable($data, 'suppliers');

        // 将数组null值转为空
        array_walk_recursive($data, function (&$val, $key) {
            $val = ($val === null) ? '' : $val;
        });

        return Suppliers::insert($data);
    }

    /**
     * 根据子级地区ID获取顶级父级地区ID
     * @param int $region_id
     * @return array
     */
    public function get_region_level($region_id = 0)
    {
        $array = [];

        $region = [
            'region_id' => intval($region_id),
            'region_name' => '',
        ];

        while (!empty($region)) {
            $region_id = $region['parent_id'] ?? $region_id;
            $region = Region::select('parent_id', 'region_id', 'region_name')->where('region_id', $region_id)->first();

            $region = $region ? $region->toArray() : [];
            if (!empty($region)) {
                $array[] = [
                    'region_id' => intval($region['region_id']),
                    'region_name' => $region['region_name'] ?? ''

                ];
            }
        }
        $array = array_reverse($array);

        return $array;
    }

}
