<?php

namespace App\Services\Other;

use App\Models\AdminUser;
use App\Models\Agency;
use App\Models\BackOrder;
use App\Models\DeliveryOrder;
use App\Models\OrderInfo;
use App\Models\Region;
use App\Repositories\Common\BaseRepository;

class AgencyManageService
{
    protected $baseRepository;

    public function __construct(
        BaseRepository $baseRepository
    )
    {
        $this->baseRepository = $baseRepository;
    }

    /**
     * 取得办事处列表
     * @return  array
     */
    public function getAgencyList()
    {
        /* 初始化分页参数 */
        $filter = [];
        $filter['sort_by'] = empty($_REQUEST['sort_by']) ? 'agency_id' : trim($_REQUEST['sort_by']);
        $filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'DESC' : trim($_REQUEST['sort_order']);

        $row = Agency::whereRaw(1);

        $res = $record_count = $row;

        /* 查询记录总数，计算分页数 */
        $filter['record_count'] = $record_count->count();
        $filter = page_and_size($filter);

        /* 查询记录 */
        $res = $res->orderBy($filter['sort_by'], $filter['sort_order']);

        if ($filter['start'] > 0) {
            $res = $res->skip($filter['start']);
        }

        if ($filter['page_size'] > 0) {
            $res = $res->take($filter['page_size']);
        }

        $res = $this->baseRepository->getToArrayGet($res);

        $arr = [];
        if ($res) {
            foreach ($res as $rows) {
                $arr[] = $rows;
            }
        }

        return ['agency' => $arr, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']];
    }

    /**
     * 删除办事处
     *
     * @param int $id
     */
    public function agencyUpdate($id = 0, $other = [])
    {
        if ($id) {
            if (is_array($id)) {
                AdminUser::whereIn('agency_id', $id)->update($other);
                Region::whereIn('agency_id', $id)->update($other);
                OrderInfo::whereIn('agency_id', $id)->update($other);
                DeliveryOrder::whereIn('agency_id', $id)->update($other);
                BackOrder::whereIn('agency_id', $id)->update($other);
            } else {
                AdminUser::where('agency_id', $id)->update($other);
                Region::where('agency_id', $id)->update($other);
                OrderInfo::where('agency_id', $id)->update($other);
                DeliveryOrder::where('agency_id', $id)->update($other);
                BackOrder::where('agency_id', $id)->update($other);
            }
        }
    }

    /**
     * 取得所有管理员，标注哪些是该办事处的('this')，哪些是空闲的('free')，哪些是别的办事处的('other')
     *
     * @param int $agency_id
     * @return array
     */
    public function getAdminList($agency_id = 0)
    {
        $admin_list = AdminUser::where('ru_id', 0);
        $admin_list = $this->baseRepository->getToArrayGet($admin_list);
        if ($admin_list) {
            foreach ($admin_list as $key => $row) {
                if ($row['agency_id'] == 0) {
                    $admin_list[$key]['type'] = 'free';
                } elseif ($row['agency_id'] == $agency_id) {
                    $admin_list[$key]['type'] = 'this';
                } else {
                    $admin_list[$key]['type'] = 'other';
                }
            }
        }

        return $admin_list;
    }
}
