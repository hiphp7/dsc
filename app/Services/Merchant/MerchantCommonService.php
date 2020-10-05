<?php

namespace App\Services\Merchant;

use App\Models\AdminUser;
use App\Models\Article;
use App\Models\MerchantsCategoryTemporarydate;
use App\Models\MerchantsDtFile;
use App\Models\MerchantsShopInformation;
use App\Models\MerchantsStepsFields;
use App\Models\MerchantsStepsProcess;
use App\Models\SellerDomain;
use App\Models\SellerShopinfo;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\DscRepository;
use App\Repositories\Common\TimeRepository;

class MerchantCommonService
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
     * 获取会员申请入驻商家信息
     *
     * @param int $user_id
     * @return array
     */
    public function getMerchantsShopInformation($user_id = 0)
    {
        $res = MerchantsShopInformation::where('user_id', $user_id);
        $res = $this->baseRepository->getToArrayFirst($res);

        return $res;
    }

    /**
     * 获得店铺入驻流程扩展信息
     *
     * @access  public
     * @param int $seller_id
     * @return  array
     */
    public function getMerchantsStepsFields($seller_id = 0)
    {
        $row = MerchantsStepsFields::where('user_id', $seller_id);
        $row = $this->baseRepository->getToArrayFirst($row);

        return $row;
    }

    /**
     * 调取店铺名称
     *
     * @param int $ru_id
     * @param int $type
     * @return array|bool|\Illuminate\Cache\CacheManager|mixed|string
     * @throws \Exception
     */
    public function getShopName($ru_id = 0, $type = 0)
    {
        $Shopinfo_cache_name = 'SellerShopinfo_' . $ru_id;
        $shopinfo = cache($Shopinfo_cache_name);

        if (is_null($shopinfo)) {
            $shopinfo = SellerShopinfo::where('ru_id', $ru_id);
            $shopinfo = $this->baseRepository->getToArrayFirst($shopinfo);

            $shopinfo['shop_url'] = $this->dscRepository->sellerUrl($ru_id);

            cache()->forever($Shopinfo_cache_name, $shopinfo);
        }

        $MerchantsShopInformation_cache_name = 'MerchantsShopInformation_' . $ru_id;
        $shop_information = cache($MerchantsShopInformation_cache_name);

        if (is_null($shop_information)) {
            $shop_information = MerchantsShopInformation::where('user_id', $ru_id);
            $shop_information = $this->baseRepository->getToArrayFirst($shop_information);

            cache()->forever($MerchantsShopInformation_cache_name, $shop_information);
        }

        if ($shopinfo) {
            $shop_information = $this->baseRepository->getArrayMerge($shopinfo, $shop_information);

            $shop_information['shoprz_brandName'] = isset($shop_information['shoprz_brandName']) ? $shop_information['shoprz_brandName'] : '';
            $shop_information['rz_shopName'] = isset($shop_information['rz_shopName']) ? $shop_information['rz_shopName'] : '';
            $shop_information['self_run'] = isset($shop_information['self_run']) ? $shop_information['self_run'] : '';
            $shop_information['shopNameSuffix'] = isset($shop_information['shopNameSuffix']) ? $shop_information['shopNameSuffix'] : '';
            $shop_information['shop_close'] = isset($shop_information['shop_close']) ? $shop_information['shop_close'] : '';
            $shop_information['is_IM'] = isset($shop_information['is_IM']) ? $shop_information['is_IM'] : 0;

            $shopinfo['self_run'] = $shop_information['self_run']; //自营店铺传值
            $shop_information['shop_name'] = $shop_information['shoprz_brandName'] . $shop_information['shopNameSuffix'];

            if (empty($shop_information)) {
                $shop_information['shop_name'] = $shopinfo['shop_name'];
            }

            if ($type == 3) { //搜索店铺
                $shop_information['shop_name'] = $shop_information['shoprz_brandName'];
                $shop_information['rz_shopName'] = str_replace([lang('merchants.flagship_store'), lang('merchants.specialty_store'), lang('merchants.franchise_store')], '', $shop_information['rz_shopName']);
            }
            if (isset($shopinfo['shopname_audit']) && $shopinfo['shopname_audit'] == 1) {

                $check_sellername = $shopinfo['check_sellername'] ?? 0;

                if ($check_sellername == 1) { //期望店铺名称
                    $shop_name = $shop_information['rz_shopName'];
                } elseif ($check_sellername == 2) {
                    $shop_name = $shopinfo['shop_name'];
                } else {
                    if ($ru_id > 0) {
                        $shop_name = $shop_information['shop_name'];
                    } else {
                        $shop_name = $shopinfo['shop_name'];
                    }
                }
            } else {
                $shop_name = $shop_information['rz_shopName']; //默认店铺名称
            }

            if ($type == 1) {
                return $shop_name;
            } elseif ($type == 2) {
                return $shopinfo;
            } elseif ($type == 3) {
                if (isset($shop_information['shopNameSuffix']) && !empty($shop_information['shopNameSuffix'])) {
                    if (strpos($shop_name, $shop_information['shopNameSuffix']) === false && $shopinfo['check_sellername'] == 0) {
                        $shop_name .= $shop_information['shopNameSuffix'];
                    }
                }

                $res = [
                    'shop_name' => $shop_name,
                    'shopNameSuffix' => isset($shop_information['shopNameSuffix']) ?: '',
                    'shopinfo' => $shopinfo,
                    'shop_information' => $shop_information
                ];
                return $res;
            } else {
                $shop_information['shop_name'] = $shop_name;
                return $shop_information;
            }
        } else {
            if ($type == 1) {
                return '';
            } else {
                return [
                    'shoprz_brandName' => $shop_information['shoprz_brandName'] ?? '',
                    'rz_shopName' => $shop_information['rz_shopName'] ?? '',
                    'self_run' => $shop_information['self_run'] ?? '',
                    'shopNameSuffix' => $shop_information['shopNameSuffix'] ?? '',
                    'shop_close' => $shop_information['shop_close'] ?? '',
                    'is_IM' => $shop_information['is_IM'] ?? ''
                ];
            }
        }
    }

    /**
     * 商家ULR地址
     *
     * @param int $ru_id
     * @param array $build_uri
     * @return mixed
     * @throws \Exception
     */
    public function getSellerDomainUrl($ru_id = 0, $build_uri = [])
    {
        $build_uri['cid'] = isset($build_uri['cid']) ? $build_uri['cid'] : 0;
        $build_uri['urid'] = isset($build_uri['urid']) ? $build_uri['urid'] : $ru_id;
        unset($build_uri['append']);

        $other = [];
        if ($build_uri['cid'] > 0) {
            $other = [
                'cid' => $build_uri['cid']
            ];
        }

        $res = [];
        $res['domain_name'] = $this->dscRepository->sellerUrl($build_uri['urid'], $other);

        return $res;
    }

    /**
     * 处理店铺二级域名
     *
     * @param int $ru_id
     * @return mixed
     */
    public function getSellerDomainInfo($ru_id = 0)
    {
        $row = SellerDomain::where('ru_id', $ru_id);
        $row = $this->baseRepository->getToArrayFirst($row);

        if (!$row) {
            $row['domain_name'] = '';
            $row['is_enable'] = '';
            $row['validity_time'] = '';
        }

        return $row;
    }

    /**
     * 入驻须知
     * @param int $process_steps
     * @return mixed
     */
    public function getMerchantsStepsProcess($process_steps = 0)
    {
        if (empty($process_steps)) {
            return [];
        }

        $model = MerchantsStepsProcess::where('process_steps', $process_steps);

        $result = $this->baseRepository->getToArrayFirst($model);

        if ($result['process_article'] > 0) {

            $article = Article::where('article_id', $result['process_article']);
            $article = $this->baseRepository->getToArrayFirst($article);

            if ($article) {
                if ($article['content']) {
                    // 过滤样式 手机自适应
                    $article['content'] = $this->dscRepository->contentStyleReplace($article['content']);
                    // 显示文章详情图片 （本地或OSS）
                    $article['content'] = $this->dscRepository->getContentImgReplace($article['content']);
                }
            }

            $result['article_content'] = $article['content'] ?? [];
        }

        return $result;
    }


    /**
     * 更新申请进度
     * @param int $fid
     * @param int $user_id
     * @param array $data
     * @return bool
     */
    public function updateMerchantsStepsFields($fid = 0, $user_id = 0, $data = [])
    {
        if (empty($fid) || empty($user_id) || empty($data)) {
            return false;
        }

        $data = $this->baseRepository->getArrayfilterTable($data, 'merchants_steps_fields');

        // 将数组null值转为空
        array_walk_recursive($data, function (&$val, $key) {
            $val = ($val === null) ? '' : $val;
        });

        return MerchantsStepsFields::where('fid', $fid)->where('user_id', $user_id)->update($data);
    }

    /**
     * 新增申请进度
     * @param array $data
     * @return bool
     */
    public function createMerchantsStepsFields($data = [])
    {
        if (empty($data)) {
            return false;
        }

        $count = MerchantsStepsFields::where('user_id', $data['user_id'])->count();

        if (empty($count)) {
            $data = $this->baseRepository->getArrayfilterTable($data, 'merchants_steps_fields');

            // 将数组null值转为空
            array_walk_recursive($data, function (&$val, $key) {
                $val = ($val === null) ? '' : $val;
            });

            return MerchantsStepsFields::insert($data);
        }

        return false;
    }

    /**
     * 删除商家入驻流程填写分类临时信息
     * @param int $user_id
     * @return mixed
     */
    public function deleleMerchantsCategoryTemporarydate($user_id = 0)
    {
        if (empty($user_id)) {
            return false;
        }

        return MerchantsCategoryTemporarydate::where('user_id', $user_id)->where('is_add', 0)->delete();
    }

    /**
     * 更新商家入驻流程填写分类临时信息
     * @param int $user_id
     * @return mixed
     */
    public function updateMerchantsCategoryTemporarydate($user_id = 0)
    {
        if (empty($user_id)) {
            return false;
        }

        return MerchantsCategoryTemporarydate::where('user_id', $user_id)->where('is_add', 0)->update(['is_add' => 1]);
    }

    /**
     * 新增入驻商家信息
     * @param array $data
     * @return bool
     */
    public function createMerchantsShopInformation($data = [])
    {
        if (empty($data)) {
            return false;
        }

        $data = $this->baseRepository->getArrayfilterTable($data, 'merchants_shop_information');

        // 将数组null值转为空
        array_walk_recursive($data, function (&$val, $key) {
            $val = ($val === null) ? '' : $val;
        });

        // 待审核
        $data['steps_audit'] = 1;
        $data['merchants_audit'] = 0;

        $count = MerchantsShopInformation::where('user_id', $data['user_id'])->count();

        if (empty($count)) {
            // 添加
            $data['add_time'] = $this->timeRepository->getGmTime();

            return MerchantsShopInformation::insert($data);
        } else {
            // 更新时间
            $data['update_time'] = $this->timeRepository->getGmTime();

            return MerchantsShopInformation::where('user_id', $data['user_id'])->update($data);
        }
    }

    /**
     * 删除商家入驻流程分类资质信息
     * @param int $parent_id
     * @return bool
     */
    public function deleteMerchantsDtFile($parent_id = 0)
    {
        if (empty($parent_id)) {
            return false;
        }

        return MerchantsDtFile::where('cat_id', $parent_id)->delete();
    }

    /**
     * 删除商家入驻流程填写分类临时信息
     * @param int $ct_id
     * @return bool
     */
    public function deleleMerchantsCategoryTemporarydateByCtid($ct_id = 0)
    {
        if (empty($ct_id)) {
            return false;
        }

        return MerchantsCategoryTemporarydate::where('ct_id', $ct_id)->where('is_add', 0)->delete();
    }

    /**
     * 删除商家入驻流程填写分类临时表 主分类下子分类
     *
     * @param int $cat_id
     * @param int $user_id
     * @return bool
     */
    public function deleleMerchantsCategoryTemporarydateByCateid($cat_id = 0, $user_id = 0)
    {
        if (empty($cat_id)) {
            return false;
        }

        return MerchantsCategoryTemporarydate::where('parent_id', $cat_id)->where('user_id', $user_id)->delete();
    }

    /**
     * 检查店铺名是否使用
     * @param int $user_id
     * @param string $rz_shopName
     * @return int
     */
    public function checkMerchantsShopName($user_id = 0, $rz_shopName = '')
    {
        if (empty($user_id) || empty($rz_shopName)) {
            return 0;
        }

        $res = MerchantsShopInformation::where('rz_shopName', $rz_shopName)->where('user_id', '<>', $user_id)->value('user_id');

        return $res;
    }

    /**
     * 检查店铺名是否使用
     * @param int $user_id
     * @param string $hopeLoginName
     * @return int
     */
    public function checkMerchantsHopeLoginName($user_id = 0, $hopeLoginName = '')
    {
        if (empty($user_id) || empty($hopeLoginName)) {
            return 0;
        }

        $res = AdminUser::where('user_name', $hopeLoginName)
            ->where('ru_id', '<>', $user_id)
            ->value('user_id');

        return $res;
    }

    /**
     * 获取商家域名
     *
     * @param string $shop
     * @return mixed
     */
    public function getSellerDomain($shop = '')
    {

        $res = SellerDomain::where('domain_name', $shop)
            ->where('is_enable', 1);

        $res = app(BaseRepository::class)->getToArrayFirst($res);

        if ($res && $res['validity_time'] > 0) {
            $nowTime = app(TimeRepository::class)->currentTimestamp();

            /* 关闭二级域名 */
            if ($nowTime > $res['validity_time']) {
                $res = [];
            }
        }

        return $res;
    }
}
