<?php

namespace App\Services\Ads;

use App\Models\Ad;
use App\Models\AdPosition;
use App\Models\Category;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\DscRepository;
use App\Repositories\Common\TimeRepository;
use App\Services\Common\CommonManageService;
use App\Services\Merchant\MerchantCommonService;

class AdsManageService
{
    protected $commonManageService;
    protected $baseRepository;
    protected $config;
    protected $timeRepository;
    protected $merchantCommonService;
    protected $dscRepository;

    public function __construct(
        CommonManageService $commonManageService,
        BaseRepository $baseRepository,
        TimeRepository $timeRepository,
        MerchantCommonService $merchantCommonService,
        DscRepository $dscRepository
    )
    {
        $this->commonManageService = $commonManageService;
        $this->baseRepository = $baseRepository;
        $this->timeRepository = $timeRepository;
        $this->merchantCommonService = $merchantCommonService;
        $this->dscRepository = $dscRepository;
        $this->config = $this->dscRepository->dscConfig();
    }

    /**
     * 获取广告位置列表
     *
     * @return array
     */
    public function adPositionList()
    {
        $seller = $this->commonManageService->getAdminIdSeller();

        $filter = [];

        $filter['keyword'] = !empty($_REQUEST['keyword']) ? trim($_REQUEST['keyword']) : '';
        if (isset($_REQUEST['is_ajax']) && $_REQUEST['is_ajax'] == 1) {
            $filter['keyword'] = json_str_iconv($filter['keyword']);
        }

        $filter['ru_id'] = isset($_REQUEST['ru_id']) && !empty($_REQUEST['ru_id']) ? intval($_REQUEST['ru_id']) : $seller['ru_id'];

        //管理员查询的权限 -- 店铺查询 start
        $filter['store_search'] = empty($_REQUEST['store_search']) ? 0 : intval($_REQUEST['store_search']);
        $filter['merchant_id'] = isset($_REQUEST['merchant_id']) ? intval($_REQUEST['merchant_id']) : 0;
        $filter['store_keyword'] = isset($_REQUEST['store_keyword']) ? trim($_REQUEST['store_keyword']) : '';

        $row = AdPosition::whereRaw(1);

        if ($filter['ru_id'] > 0) {
            $row = $row->where(function ($query) use ($filter) {
                $query->where('user_id', $filter['ru_id'])
                    ->orWhere('is_public', 1);
            });
        }

        if ($filter['store_search'] != 0) {
            if ($filter['ru_id'] == 0) {
                $store_type = isset($_REQUEST['store_type']) && !empty($_REQUEST['store_type']) ? intval($_REQUEST['store_type']) : 0;
                $filter['store_type'] = $store_type;

                if ($filter['store_search'] == 1) {
                    $row = $row->where('user_id', $filter['merchant_id']);
                }

                if ($filter['store_search'] > 1) {
                    $row = $row->whereHas('getMerchantsShopInformation', function ($query) use ($filter) {
                        if ($filter['store_search'] == 2) {
                            $query->where('rz_shopName', 'like', '%' . mysql_like_quote($filter['store_keyword']) . '%');
                        } elseif ($filter['store_search'] == 3) {
                            $query = $query->where('shoprz_brandName', 'like', '%' . mysql_like_quote($filter['store_keyword']) . '%');

                            if ($filter['store_type']) {
                                $query->where('shopNameSuffix', $filter['store_type']);
                            }
                        }
                    });
                }
            }
        }
        //管理员查询的权限 -- 店铺查询 end

        /* 关键字 */
        if (!empty($filter['keyword'])) {
            $row = $row->where('position_name', 'like', '%' . mysql_like_quote($filter['keyword']) . '%');
        }

        //模板类型
        $row = $row->where('theme', $this->config['template']);

        $res = $record_count = $row;

        /* 记录总数以及页数 */
        $filter['record_count'] = $record_count->count();

        $filter = page_and_size($filter);

        /* 查询数据 */
        if ($filter['start'] > 0) {
            $res = $res->skip($filter['start']);
        }

        if ($filter['page_size'] > 0) {
            $res = $res->take($filter['page_size']);
        }

        $res = $res->orderBy('position_id', 'desc');

        $res = $this->baseRepository->getToArrayGet($res);

        if ($res) {
            foreach ($res as $key => $rows) {
                $position_desc = !empty($rows['position_desc']) ? $this->dscRepository->subStr($rows['position_desc'], 50, true) : '';
                $res[$key]['position_desc'] = nl2br(htmlspecialchars($position_desc));
                $res[$key]['user_name'] = $this->merchantCommonService->getShopName($rows['user_id'], 1);
            }
        }

        return ['position' => $res, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']];
    }

    /**
     * 广告位置信息
     *
     * @param int $position_id
     * @return array
     */
    public function getPositionInfo($position_id = 0)
    {
        $row = AdPosition::where('position_id', $position_id);
        $row = $this->baseRepository->getToArrayFirst($row);

        return $row;
    }

    /**
     * 广告位置列表
     *
     * @param int $ru_id
     * @return array
     */
    public function getPositionList($ru_id = 0)
    {
        $res = AdPosition::where('theme', $this->config['template']);

        if ($ru_id > 0) {
            $res = $res->where('is_public', 1);
        }

        $res = $this->baseRepository->getToArrayGet($res);

        return $res;
    }

    /**
     * 获取广告数据列表
     *
     * @param $ru_id
     * @return array
     */
    public function getAdsList()
    {
        $Seller = $this->commonManageService->getAdminIdSeller();

        /* 过滤查询 */
        $filter = [];

        //ecmoban模板堂 --zhuo start
        $filter['keyword'] = !empty($_REQUEST['keyword']) ? trim($_REQUEST['keyword']) : '';
        if (isset($_REQUEST['is_ajax']) && $_REQUEST['is_ajax'] == 1) {
            $filter['keyword'] = json_str_iconv($filter['keyword']);
        }
        //ecmoban模板堂 --zhuo end

        $filter['ru_id'] = isset($_REQUEST['ru_id']) && !empty($_REQUEST['ru_id']) ? intval($_REQUEST['ru_id']) : $Seller['ru_id'];
        $filter['sort_by'] = empty($_REQUEST['sort_by']) ? 'ad.ad_id' : trim($_REQUEST['sort_by']);
        $filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'DESC' : trim($_REQUEST['sort_order']);
        $filter['pid'] = empty($_REQUEST['pid']) ? 0 : intval($_REQUEST['pid']);

        $row = Ad::whereRaw(1);

        if (!empty($filter['keyword'])) {
            $row = $row->where(function ($query) use ($filter) {
                $query = $query->where('ad_name', 'like', '%' . mysql_like_quote($filter['keyword']) . '%');

                $query->orWhere(function ($query) use ($filter) {
                    $query->whereHas('getAdPosition', function ($query) use ($filter) {
                        $query->where('position_name', 'like', '%' . mysql_like_quote($filter['keyword']) . '%');
                    });
                });
            });
        }

        if (!empty($filter['pid'])) {
            $row = $row->where('position_id', $filter['pid']);
        }

        if ($filter['ru_id'] > 0) {
            $row = $row->where(function ($query) use ($filter) {
                $query = $query->where('is_public', 1)
                    ->where('public_ruid', $filter['ru_id']);

                $query->orWhere(function ($query) use ($filter) {
                    $query->whereHas('getAdPosition', function ($query) use ($filter) {
                        $query->where('user_id', $filter['ru_id']);
                    });
                });
            });
        }

        $filter['template'] = $GLOBALS['_CFG']['template'];

        //模板类型
        $row = $row->whereHas('getAdPosition', function ($query) use ($filter) {
            $query->where('theme', $filter['template']);
        });

        $time = $this->timeRepository->getGmTime();
        $filter['advance_date'] = isset($_REQUEST['advance_date']) ? intval($_REQUEST['advance_date']) : 0;

        if ($filter['advance_date'] == 1) {
            $row = $row->whereRaw($time . " BETWEEN (end_time - 3600*24*3) AND end_time");
        } elseif ($filter['advance_date'] == 2) {
            $row = $row->where('end_time', '<', $time);
        }

        $res = $record_count = $row;

        /* 获得总记录数据 */
        $filter['record_count'] = $record_count->count();

        $filter = page_and_size($filter);

        $res = $res->withCount('getOrderInfo as ad_stats');

        $res = $res->with('getAdPosition');

        if ($filter['start'] > 0) {
            $res = $res->skip($filter['start']);
        }

        if ($filter['page_size'] > 0) {
            $res = $res->take($filter['page_size']);
        }

        $res = $res->orderBy($filter['sort_by'], $filter['sort_order']);

        $res = $this->baseRepository->getToArrayGet($res);

        /* 获得广告数据 */
        $idx = 0;
        $arr = [];
        if ($res) {
            foreach ($res as $key => $rows) {
                $rows['position_name'] = $rows['get_ad_position']['position_name'] ?? '';
                $rows['user_id'] = $rows['get_ad_position']['user_id'] ?? 0;

                /* 广告类型的名称 */
                $rows['type'] = ($rows['media_type'] == 0) ? $GLOBALS['_LANG']['ad_img'] : '';
                $rows['type'] .= ($rows['media_type'] == 1) ? $GLOBALS['_LANG']['ad_flash'] : '';
                $rows['type'] .= ($rows['media_type'] == 2) ? $GLOBALS['_LANG']['ad_html'] : '';
                $rows['type'] .= ($rows['media_type'] == 3) ? $GLOBALS['_LANG']['ad_text'] : '';

                /* 格式化日期 */
                $rows['start_date'] = $this->timeRepository->getLocalDate($GLOBALS['_CFG']['time_format'], $rows['start_time']);
                $rows['end_date'] = $this->timeRepository->getLocalDate($GLOBALS['_CFG']['time_format'], $rows['end_time']);

                if ($time > ($rows['end_time'] - 24 * 3600 * 3) && $time < $rows['end_time']) {
                    $rows['advance_date'] = 1;
                } elseif ($time > $rows['end_time']) {
                    $rows['advance_date'] = 2;
                }

                if ($rows['public_ruid'] == 0) {
                    $user_id = $rows['user_id'];
                } else {
                    $user_id = $rows['public_ruid'];
                }

                $rows['user_name'] = $this->merchantCommonService->getShopName($user_id, 1); //ecmoban模板堂 --zhuo

                $rows['ad_code'] = $this->dscRepository->getImagePath(DATA_DIR . '/afficheimg/' . $rows['ad_code']);

                $arr[$idx] = $rows;

                $idx++;
            }
        }

        return ['ads' => $arr, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']];
    }
    /*------------------------------------------------------ */
    //-- 获取广告位模型信息
    /*------------------------------------------------------ */
    public function getCatList($catId = 0)
    {
        $where = '1=1';
        if (empty($catId)) {
            $where = 'parent_id=0';
        } else {
            $where = 'parent_id=' . $catId;
        }
        $catList = Category::whereRaw($where)->get();
        return $catList;
    }
    /*------------------------------------------------------ */
    //-- 获取广告位模型信息
    /*------------------------------------------------------ */
    public function get_ad_model($pid)
    {
        //初始数组
        $ad_arr = [
            'ad_type' => 0,
            'ad_model_init' => '',
            'ad_model' => '',
            'ad_model_structure' => '',
            'cat_id' => []
        ];
        //模型片段
        $init_model = ['[num_id]', '[cat_id]'];
        //广告位信息
        $position_info = AdPosition::where('position_id', $pid)->first();
        if (!empty($position_info['position_model'])) {
            //$ad_arr['ad_type']=1;
            //初始广告位模型($ad_model)和模型结构($ad_model_structure)
            $ad_model = $position_info['position_model'];
            $ad_model_structure = [];
            $i = 0;
            foreach ($init_model as $model) {
                if (strpos($ad_model, $model)) {
                    if ($model == '[num_id]') {
                        $ad_arr['ad_type'] = 1;
                    }
                    if ($model == '[cat_id]') {
                        $ad_arr['ad_type'] = 2;
                    }
                    //去除[]符号
                    $ad_model_structure[$i] = str_replace(['[', ']'], ['', ''], $model);
                    $i++;
                    $ad_model = str_replace(['_' . $model . '_', '_' . $model, $model . '_', $model], [
                        '',
                        '',
                        '',
                        ''
                    ], $ad_model);
                }
            }
            if ($ad_arr['ad_type'] > 0) {
                //赋值数组
                $ad_arr['ad_model_init'] = $position_info['position_model'];
                $ad_arr['ad_model'] = $ad_model;
                $ad_arr['ad_model_structure'] = $init_model;
            }
            if (in_array('cat_id', $ad_model_structure) && in_array('num_id', $ad_model_structure)) {
                $ad_arr['ad_type'] = 3;
                //搜索已添加广告
                $ad_exist = Ad::where('ad_name', 'like', '%' . $ad_model . '%')->get();
                if (!empty($ad_exist)) {
                    $ad_arr['ad_type'] = 4;
                    //处理已存在广告(模型片段)
                    $ad_all = [];
                    foreach ($ad_exist as $key => $val) {
                        $ad_deal = explode('_', str_replace($ad_model, '', $val['ad_name']));
                        for ($j = 0; $j < count($ad_model_structure); $j++) {
                            $ad_all[$key][$ad_model_structure[$j]] = '';
                            $ad_all[$key][$ad_model_structure[$j]] = $ad_deal[$j] ?? '';
                        }
                    }
                    //合并分类下的广告
                    foreach ($ad_all as $key => $val) {
                        $ad_arr['cat_id'][$val['cat_id']]['num_id'][] = $val['num_id'];
                    }
                    foreach ($ad_arr['cat_id'] as $key => $val) {
                        //获取下一个即将添加的num_id
                        $ad_arr['cat_id'][$key]['next'] = null;
                        for ($p = 1; $p < 9999; $p++) {
                            if (!in_array($p, $val['num_id'])) {
                                $ad_arr['cat_id'][$key]['next'] = $p;
                                break;
                            }
                        }
                    }
                }
            }
        }
        return $ad_arr;
    }
}
