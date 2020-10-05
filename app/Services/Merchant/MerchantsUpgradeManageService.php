<?php

namespace App\Services\Merchant;

use App\Libraries\Image;
use App\Models\EntryCriteria;
use App\Models\SellerGrade;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\DscRepository;
use App\Services\Commission\CommissionService;
use App\Services\Order\OrderService;


class MerchantsUpgradeManageService
{
    protected $baseRepository;
    protected $merchantCommonService;
    protected $orderService;
    protected $commissionService;
    protected $dscRepository;

    public function __construct(
        BaseRepository $baseRepository,
        OrderService $orderService,
        MerchantCommonService $merchantCommonService,
        CommissionService $commissionService,
        DscRepository $dscRepository
    )
    {
        $this->baseRepository = $baseRepository;
        $this->merchantCommonService = $merchantCommonService;
        $this->orderService = $orderService;
        $this->commissionService = $commissionService;
        $this->dscRepository = $dscRepository;
    }


    /*分页*/
    public function getPzdList()
    {

        $filter['record_count'] = SellerGrade::where('is_open', 1);
        $filter = page_and_size($filter);
        /* 获活动数据 */
        $res = SellerGrade::where('is_open', 1)
            ->orderBy('id', 'ASC')
            ->offset($filter['start'])
            ->limit($filter['page_size']);
        $row = $this->baseRepository->getToArrayGet($res);

        if ($row) {
            foreach ($row as $k => $v) {
                if ($v['entry_criteria']) {
                    $entry_criteria = unserialize($v['entry_criteria']);
                    foreach ($entry_criteria as $key => $val) {
                        $criteria_name = EntryCriteria::where('id', $val)->value('criteria_name');
                        $criteria_name = $criteria_name ? $criteria_name : '';
                        if ($criteria_name) {
                            $entry_criteria[$key] = $criteria_name;
                        }
                    }

                    $row[$k]['entry_criteria'] = implode(" , ", $entry_criteria);
                    $row[$k]['grade_img'] = $this->dscRepository->getImagePath($v['grade_img']);
                }
            }
        }

        $arr = ['pzd_list' => $row, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']];
        return $arr;
    }


    /*获取申请等级的入驻标准*/
    public function getEntryCriteria($entry_criteria = '')
    {
        $entry_criteria = unserialize($entry_criteria);//反序列化等级入驻标准
        $rel = '';
        if (!empty($entry_criteria)) {
            $entry_criteria = $this->baseRepository->getExplode($entry_criteria);
            $res = EntryCriteria::whereIn('id', $entry_criteria);
            $rel = $this->baseRepository->getToArrayGet($res);
            $rel['count_charge'] = 0;
            if ($rel) {
                $count_charge = 0;
                $no_cumulative_price = 0;

                foreach ($rel as $k => $v) {
                    $res = EntryCriteria::where('parent_id', $v['id']);
                    $child = $this->baseRepository->getToArrayGet($res);
                    foreach ($child as $key => $val) {
                        if ($val['type'] == 'select' && $val['option_value'] != '') {
                            $child[$key]['option_value'] = explode(',', $val['option_value']);
                        }

                        $count_charge += $val['charge'];

                        if ($val['is_cumulative'] == 0) {
                            $no_cumulative_price += $val['charge'];
                        }
                    }

                    $rel[$k]['child'] = $child;
                }

                $rel['count_charge'] = $count_charge;
                $rel['no_cumulative_price'] = $no_cumulative_price;
            }
        }
        return $rel;
    }

    /**
     * 保存申请时的上传图片
     *
     * @access  public
     * @param int $image_files 上传图片数组
     * @param int $file_id 图片对应的id数组
     * @return  void
     */
    public function uploadApplyFile($image_files = [], $file_id = [], $url = [])
    {
        $image = new Image(['bgcolor' => $GLOBALS['_CFG']['bgcolor']]);

        /* 是否成功上传 */
        foreach ($file_id as $v) {
            $flag = false;
            if (isset($image_files['error'])) {
                if ($image_files['error'][$v] == 0) {
                    $flag = true;
                }
            } else {
                if ($image_files['tmp_name'][$v] != 'none' && $image_files['tmp_name'][$v]) {
                    $flag = true;
                }
            }
            if ($flag) {
                /*生成上传信息的数组*/
                $upload = [
                    'name' => $image_files['name'][$v],
                    'type' => $image_files['type'][$v],
                    'tmp_name' => $image_files['tmp_name'][$v],
                    'size' => $image_files['size'][$v],
                ];
                if (isset($image_files['error'])) {
                    $upload['error'] = $image_files['error'][$v];
                }

                $img_original = $image->upload_image($upload);
                if ($img_original === false) {
                    return sys_msg($image->error_msg(), 1, [], false);
                }
                $img_url[$v] = $img_original;
                /*删除原文件*/
                if (!empty($url[$v])) {
                    dsc_unlink(storage_public($url[$v]));
                }
            }
        }
        if (!empty($img_url)) {
            return $img_url;
        } else {
            return false;
        }
    }
}
