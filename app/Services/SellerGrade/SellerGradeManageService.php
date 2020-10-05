<?php

namespace App\Services\SellerGrade;

use App\Libraries\Image;
use App\Models\EntryCriteria;
use App\Models\SellerGrade;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\DscRepository;
use App\Services\Common\CommonManageService;
use App\Services\Merchant\MerchantCommonService;

class SellerGradeManageService
{

    protected $baseRepository;
    protected $commonManageService;
    protected $merchantCommonService;
    protected $dscRepository;

    public function __construct(
        BaseRepository $baseRepository,
        CommonManageService $commonManageService,
        MerchantCommonService $merchantCommonService,
        DscRepository $dscRepository
    )
    {
        $this->baseRepository = $baseRepository;
        $this->commonManageService = $commonManageService;
        $this->merchantCommonService = $merchantCommonService;
        $this->dscRepository = $dscRepository;
    }


    /* 上传文件 */

    public function uploadArticleFile($upload = [])
    {
        $file_dir = storage_public(DATA_DIR . "/seller_grade");
        if (!file_exists($file_dir)) {
            if (!make_dir($file_dir)) {
                /* 创建目录失败 */
                return false;
            }
        }

        $filename = app(Image::class)->random_filename() . substr($upload['name'], strpos($upload['name'], '.'));
        $path = storage_public(DATA_DIR . "/seller_grade/" . $filename);

        if (move_upload_file($upload['tmp_name'], $path)) {
            return DATA_DIR . "/seller_grade/" . $filename;
        } else {
            return false;
        }
    }

    /* 分页 */
    public function getPzdList()
    {

        $res = SellerGrade::whereRaw(1);

        $filter['record_count'] = $res->count();
        $filter = page_and_size($filter);
        /* 获活动数据 */

        $filter['keywords'] = isset($filter['keywords']) ? stripslashes($filter['keywords']) : '';

        $res = $res->offset($filter['start'])->limit($filter['page_size']);
        $row = $this->baseRepository->getToArrayGet($res);

        foreach ($row as $k => $v) {
            if (isset($v['entry_criteria']) && $v['entry_criteria']) {
                $entry_criteria = unserialize($v['entry_criteria']);
                foreach ($entry_criteria as $key => $val) {
                    $criteria_name = EntryCriteria::where('id', $val)->value('criteria_name');
                    $criteria_name = $criteria_name ? $criteria_name : '';

                    if ($criteria_name) {
                        $entry_criteria[$key] = $criteria_name;
                    }
                }
                $row[$k]['entry_criteria'] = implode(" , ", $entry_criteria);
            }

            $row[$k]['grade_img'] = empty($v['grade_img']) ? '' : $this->dscRepository->getImagePath($v['grade_img']);
        }

        $arr = ['pzd_list' => $row, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']];
        return $arr;
    }

    public function httpGetData($url)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_URL, $url);
        ob_start();
        curl_exec($ch);
        $return_content = ob_get_contents();
        ob_end_clean();

        $return_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        return $return_content;
    }
}
