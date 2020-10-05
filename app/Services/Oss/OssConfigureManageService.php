<?php

namespace App\Services\Oss;


use App\Libraries\Shop;
use App\Models\OssConfigure;
use App\Repositories\Common\BaseRepository;


class OssConfigureManageService
{

    protected $baseRepository;
    protected $shop;

    public function __construct(
        BaseRepository $baseRepository,
        Shop $shop
    )
    {
        $this->baseRepository = $baseRepository;
        $this->shop = $shop;
    }

    /**
     *  返回bucket列表数据
     *
     * @access  public
     * @param
     *
     * @return void
     */
    public function bucketList()
    {
        /* 过滤条件 */
        $filter['keywords'] = empty($_REQUEST['keywords']) ? '' : trim($_REQUEST['keywords']);
        if (isset($_REQUEST['is_ajax']) && $_REQUEST['is_ajax'] == 1) {
            $filter['keywords'] = json_str_iconv($filter['keywords']);
        }

        $filter['sort_by'] = empty($_REQUEST['sort_by']) ? 'id' : trim($_REQUEST['sort_by']);
        $filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'DESC' : trim($_REQUEST['sort_order']);

        $filter['record_count'] = OssConfigure::count();
        /* 分页大小 */
        $filter = page_and_size($filter);

        $res = OssConfigure::orderBy($filter['sort_by'], $filter['sort_order'])
            ->offset($filter['start'])
            ->limit($filter['page_size']);
        $bucket_list = $this->baseRepository->getToArrayGet($res);

        $count = count($bucket_list);

        for ($i = 0; $i < $count; $i++) {
            $regional = substr($bucket_list[$i]['regional'], 0, 2);

            $http = $this->shop->http();

            if ($regional == 'us' || $regional == 'ap') {
                $outside_site = $http . $bucket_list[$i]['bucket'] . ".oss-" . $bucket_list[$i]['regional'] . ".aliyuncs.com";
                $inside_site = $http . $bucket_list[$i]['bucket'] . ".oss-" . $bucket_list[$i]['regional'] . "-internal.aliyuncs.com";
            } else {
                $outside_site = $http . $bucket_list[$i]['bucket'] . ".oss-cn-" . $bucket_list[$i]['regional'] . ".aliyuncs.com";
                $inside_site = $http . $bucket_list[$i]['bucket'] . ".oss-cn-" . $bucket_list[$i]['regional'] . "-internal.aliyuncs.com";
            }

            $bucket_list[$i]['outside_site'] = $outside_site;
            $bucket_list[$i]['inside_site'] = $inside_site;

            if ($bucket_list[$i]['regional'] == 'shanghai') {
                $bucket_list[$i]['regional_name'] = $GLOBALS['_LANG']['regional_name_1'];
            } elseif ($bucket_list[$i]['regional'] == 'hangzhou') {
                $bucket_list[$i]['regional_name'] = $GLOBALS['_LANG']['regional_name_2'];
            } elseif ($bucket_list[$i]['regional'] == 'shenzhen') {
                $bucket_list[$i]['regional_name'] = $GLOBALS['_LANG']['regional_name_3'];
            } elseif ($bucket_list[$i]['regional'] == 'beijing') {
                $bucket_list[$i]['regional_name'] = $GLOBALS['_LANG']['regional_name_4'];
            } elseif ($bucket_list[$i]['regional'] == 'qingdao') {
                $bucket_list[$i]['regional_name'] = $GLOBALS['_LANG']['regional_name_5'];
            } elseif ($bucket_list[$i]['regional'] == 'hongkong') {
                $bucket_list[$i]['regional_name'] = $GLOBALS['_LANG']['regional_name_6'];
            } elseif ($bucket_list[$i]['regional'] == 'us-west-1') {
                $bucket_list[$i]['regional_name'] = $GLOBALS['_LANG']['regional_name_7'];
            } elseif ($bucket_list[$i]['regional'] == 'ap-southeast-1') {
                $bucket_list[$i]['regional_name'] = $GLOBALS['_LANG']['regional_name_8'];
            }
        }

        $arr = ['bucket_list' => $bucket_list, 'filter' => $filter,
            'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']];

        return $arr;
    }
}
