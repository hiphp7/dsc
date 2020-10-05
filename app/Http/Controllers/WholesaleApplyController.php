<?php

namespace App\Http\Controllers;

use App\Repositories\Common\DscRepository;
use App\Services\Article\ArticleCommonService;
use App\Services\Wholesale\CategoryService;
use App\Services\Wholesale\SuppliersService;
use App\Services\Wholesale\WholesaleService;

/**
 * 供应商入驻
 * Class WholesaleApplyController
 * @package App\Http\Controllers
 */
class WholesaleApplyController extends InitController
{
    protected $wholesaleService;
    protected $articleCommonService;
    protected $suppliersService;
    protected $categoryService;
    protected $dscRepository;

    public function __construct(
        WholesaleService $wholesaleService,
        ArticleCommonService $articleCommonService,
        SuppliersService $suppliersService,
        CategoryService $categoryService,
        DscRepository $dscRepository
    )
    {
        $this->wholesaleService = $wholesaleService;
        $this->articleCommonService = $articleCommonService;
        $this->suppliersService = $suppliersService;
        $this->categoryService = $categoryService;
        $this->dscRepository = $dscRepository;
    }

    public function index()
    {
        $user_id = session('user_id', 0);

        /* ------------------------------------------------------ */
        //-- act 操作项的初始化
        /* ------------------------------------------------------ */
        $act = request()->input('act', 'index');
        $act = $act ? $act : 'index';
        // 供应商入驻
        if ($act == 'index') {

            // 普通会员或商家 供应商入驻
            if (empty($user_id)) {
                return show_message($GLOBALS['_LANG']['not_login_user'], $GLOBALS['_LANG']['login_now'], 'user.php?act=login', 'error');
            }

            $suppliers = $this->suppliersService->suppliersInfo($user_id);

            $region_level = [];
            if ($suppliers) {
                $this->smarty->assign('supplier', $suppliers);

                $region_level = get_region_level($suppliers['region_id']);

                $region_level[0] = $region_level[0] ?? 1;
                $region_level[1] = $region_level[1] ?? 0;
                $region_level[2] = $region_level[2] ?? 0;

                $country_list = get_regions(0, 0);
                $province_list = get_regions(1, $region_level[0]);
                $city_list = get_regions(2, $region_level[1]);
                $district_list = get_regions(3, $region_level[2]);
            } else {
                /* 取得国家列表、商店所在国家、商店所在国家的省列表 */
                $country_list = get_regions();
                $province_list = get_regions(1, 1);
                $city_list = get_regions(2, 2);
                $district_list = get_regions(3, 3);
            }

            $this->smarty->assign('region_level', $region_level);
            $this->smarty->assign('country_list', $country_list);
            $this->smarty->assign('province_list', $province_list);
            $this->smarty->assign('city_list', $city_list);
            $this->smarty->assign('district_list', $district_list);

            // 语言包
            $_lang = array_merge($GLOBALS['_LANG'], lang('seller/common'), lang('seller/merchants_upgrade'));
            $this->smarty->assign('lang', $_lang);

            $cat_list = $this->categoryService->getCategoryList();
            $this->smarty->assign('cat_list', $cat_list);

            // 显示页面
            assign_template('wholesale');
            $position = assign_ur_here();

            $get_wholsale_navigator = $this->wholesaleService->getWholsaleNavigator();
            $this->smarty->assign('get_wholsale_navigator', $get_wholsale_navigator);

            $this->smarty->assign('page_title', $position['title']);    // 页面标题
            $this->smarty->assign('ur_here', $position['ur_here']);  // 当前位置
            $this->smarty->assign('helps', $this->articleCommonService->getShopHelp());       // 网店帮助
            return $this->smarty->display('wholesale_apply.dwt');
        } elseif ($act == 'do_apply') {
            // 供应商入驻提交
            if (request()->isMethod('post')) {

                if (empty($user_id)) {
                    return show_message($GLOBALS['_LANG']['not_login_user'], $GLOBALS['_LANG']['login_now'], 'user.php?act=login', 'error');
                }

                $data = request()->except(['act', '_token']);

                $data['user_id'] = $user_id;
                $data['region_id'] = empty($data['district']) ? '' : addslashes(trim($data['district']));

                if (empty($data['suppliers_name'])) {
                    return show_message(lang('seller/merchants_upgrade.js_languages.input_supplier_name'), null, '', 'error');
                }
                if (empty($data['real_name'])) {
                    return show_message(lang('seller/merchants_upgrade.js_languages.input_name'), null, '', 'error');
                }
                if (empty($data['self_num'])) {
                    return show_message(lang('seller/merchants_upgrade.js_languages.input_user_id'), null, '', 'error');
                }
                if (empty($data['mobile_phone'])) {
                    return show_message(lang('seller/merchants_upgrade.js_languages.input_iphone'), null, '', 'error');
                }
                if (empty($data['email'])) {
                    return show_message(lang('seller/merchants_upgrade.js_languages.input_email'), null, '', 'error');
                }
                if (empty($data['company_name'])) {
                    return show_message(lang('seller/merchants_upgrade.js_languages.input_company_name'), null, '', 'error');
                }
                if (empty($data['company_address'])) {
                    return show_message(lang('seller/merchants_upgrade.js_languages.input_company_address'), null, '', 'error');
                }

                /**
                 * 上传图片
                 */
                // 已上传
                $front_of_id_card_path = $data['front_of_id_card_path'] ?? '';
                $reverse_of_id_card_path = $data['reverse_of_id_card_path'] ?? '';
                $suppliers_logo_path = $data['suppliers_logo_path'] ?? '';

                $front_of_id_card_path = $this->dscRepository->editUploadImage($front_of_id_card_path);
                $reverse_of_id_card_path = $this->dscRepository->editUploadImage($reverse_of_id_card_path);
                $suppliers_logo_path = $this->dscRepository->editUploadImage($suppliers_logo_path);

                // 新上传
                $front_of_id_card = request()->file('front_of_id_card');
                $reverse_of_id_card = request()->file('reverse_of_id_card');
                $suppliers_logo = request()->file('suppliers_logo');


                if (
                    ($front_of_id_card && $front_of_id_card->isValid()) ||
                    ($reverse_of_id_card && $reverse_of_id_card->isValid()) ||
                    ($suppliers_logo && $suppliers_logo->isValid())
                ) {
                    // 验证文件大小
                    if (
                        ($front_of_id_card && $front_of_id_card->getSize() > 10 * 1024 * 1024) ||
                        ($reverse_of_id_card && $reverse_of_id_card->getSize() > 10 * 1024 * 1024) ||
                        ($suppliers_logo && $suppliers_logo->getSize() > 10 * 1024 * 1024)
                    ) {
                        return show_message(lang('seller/merchants_upgrade.file_size_limit'), null, '', 'error');
                    }
                    // 验证文件格式
                    if (
                        ($front_of_id_card && !in_array($front_of_id_card->getClientMimeType(), ['image/jpeg', 'image/png'])) ||
                        ($reverse_of_id_card && !in_array($reverse_of_id_card->getClientMimeType(), ['image/jpeg', 'image/png'])) ||
                        ($suppliers_logo && !in_array($suppliers_logo->getClientMimeType(), ['image/jpeg', 'image/png']))
                    ) {
                        return show_message(lang('seller/merchants_upgrade.not_file_type'), null, '', 'error');
                    }
                    $result = $this->upload('data/idcard', false);

                    if (isset($result['front_of_id_card']['error']) && $result['front_of_id_card']['error'] > 0) {
                        return show_message($result['front_of_id_card']['message'], null, '', 'error');
                    }
                    if (isset($result['reverse_of_id_card']['error']) && $result['reverse_of_id_card']['error'] > 0) {
                        return show_message($result['reverse_of_id_card']['message'], null, '', 'error');
                    }
                    if (isset($result['suppliers_logo']['error']) && $result['suppliers_logo']['error'] > 0) {
                        return show_message($result['suppliers_logo']['message'], null, '', 'error');
                    }
                }

                if ($front_of_id_card && $front_of_id_card->isValid() && isset($result['front_of_id_card']['file_name'])) {
                    $data['front_of_id_card'] = 'data/idcard/' . $result['front_of_id_card']['file_name'];
                } else {
                    $data['front_of_id_card'] = $front_of_id_card_path;
                }
                if ($reverse_of_id_card && $reverse_of_id_card->isValid() && isset($result['reverse_of_id_card']['file_name'])) {
                    $data['reverse_of_id_card'] = 'data/idcard/' . $result['reverse_of_id_card']['file_name'];
                } else {
                    $data['reverse_of_id_card'] = $reverse_of_id_card_path;
                }
                if ($suppliers_logo && $suppliers_logo->isValid() && isset($result['suppliers_logo']['file_name'])) {
                    $data['suppliers_logo'] = 'data/idcard/' . $result['suppliers_logo']['file_name'];
                } else {
                    $data['suppliers_logo'] = $suppliers_logo_path;
                }

                if (empty($data['front_of_id_card'])) {
                    return show_message(lang('seller/merchants_upgrade.js_languages.upload_user_id_positive'), null, '', 'error');
                }
                if (empty($data['reverse_of_id_card'])) {
                    return show_message(lang('seller/merchants_upgrade.js_languages.upload_other_side_user_id'), null, '', 'error');
                }
                if (empty($data['suppliers_logo'])) {
                    return show_message(lang('seller/merchants_upgrade.js_languages.upload_supplier_logo'), null, '', 'error');
                }

                // oss图片处理
                $file_arr = [
                    'front_of_id_card' => $data['front_of_id_card'],
                    'reverse_of_id_card' => $data['reverse_of_id_card'],
                    'suppliers_logo' => $data['suppliers_logo'],
                    'front_of_id_card_path' => $front_of_id_card_path,
                    'reverse_of_id_card_path' => $reverse_of_id_card_path,
                    'suppliers_logo_path' => $suppliers_logo_path,
                ];
                $file_arr = $this->dscRepository->transformOssFile($file_arr);

                $data['front_of_id_card'] = $file_arr['front_of_id_card'];
                $data['reverse_of_id_card'] = $file_arr['reverse_of_id_card'];
                $data['suppliers_logo'] = $file_arr['suppliers_logo'];
                $front_of_id_card_path = $file_arr['front_of_id_card_path'];
                $reverse_of_id_card_path = $file_arr['reverse_of_id_card_path'];
                $suppliers_logo_path = $file_arr['suppliers_logo_path'];

                $suppliers = $this->suppliersService->suppliersInfo($user_id);
                if (!empty($suppliers)) {
                    // 删除原图片
                    if ($data['front_of_id_card'] && $front_of_id_card_path != $data['front_of_id_card']) {
                        $front_of_id_card_path = strpos($front_of_id_card_path, 'no_image') == false ? $front_of_id_card_path : ''; // 不删除默认空图片
                        $this->remove($front_of_id_card_path);
                    }
                    if ($data['reverse_of_id_card'] && $reverse_of_id_card_path != $data['reverse_of_id_card']) {
                        $reverse_of_id_card_path = strpos($reverse_of_id_card_path, 'no_image') == false ? $reverse_of_id_card_path : ''; // 不删除默认空图片
                        $this->remove($reverse_of_id_card_path);
                    }
                    if ($data['suppliers_logo'] && $suppliers_logo_path != $data['suppliers_logo']) {
                        $suppliers_logo_path = strpos($suppliers_logo_path, 'no_image') == false ? $suppliers_logo_path : ''; // 不删除默认空图片
                        $this->remove($suppliers_logo_path);
                    }

                    // 更新
                    $result = $this->suppliersService->updateSuppliers($user_id, $data);
                    if ($result) {
                        return show_message(lang('seller/merchants_upgrade.info_edit_success_wait'), '', 'wholesale_apply.php');
                    }

                } else {
                    // 新增
                    $result = $this->suppliersService->createSuppliers($data);
                    if ($result) {
                        return show_message(lang('seller/merchants_upgrade.apply_success_wait'), '', 'wholesale_apply.php');
                    }
                }

                return show_message(lang('seller/merchants_upgrade.apply_fail'), '', 'wholesale_apply.php', 'error');
            }
        }
    }
}
