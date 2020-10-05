<?php

namespace App\Modules\Admin\Controllers;

use App\Libraries\Image;
use App\Models\AdminUser;
use App\Models\Brand;
use App\Models\Goods;
use App\Models\LinkBrand;
use App\Models\MerchantsShopBrand;
use App\Models\MerchantsShopBrandfile;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\DscRepository;
use App\Services\Merchant\MerchantCommonService;
use App\Services\Merchant\MerchantsBrandManageService;
use App\Services\Store\StoreCommonService;

/**
 * 管理中心品牌管理
 */
class MerchantsBrandController extends InitController
{
    protected $dscRepository;
    protected $merchantCommonService;
    protected $baseRepository;
    protected $merchantsBrandManageService;
    protected $storeCommonService;

    public function __construct(
        DscRepository $dscRepository,
        MerchantCommonService $merchantCommonService,
        BaseRepository $baseRepository,
        MerchantsBrandManageService $merchantsBrandManageService,
        StoreCommonService $storeCommonService
    )
    {
        $this->dscRepository = $dscRepository;
        $this->merchantCommonService = $merchantCommonService;
        $this->baseRepository = $baseRepository;
        $this->merchantsBrandManageService = $merchantsBrandManageService;
        $this->storeCommonService = $storeCommonService;
    }

    public function index()
    {
        $image = new Image(['bgcolor' => $GLOBALS['_CFG']['bgcolor']]);

        $adminru = get_admin_ru_id();
        if ($adminru['ru_id'] == 0) {
            $this->smarty->assign('priv_ru', 1);
        } else {
            $this->smarty->assign('priv_ru', 0);
        }

        $this->smarty->assign('ru_id', $adminru['ru_id']);

        $this->smarty->assign('menu_select', ['action' => '02_cat_and_goods', 'current' => '07_merchants_brand']);

        /*------------------------------------------------------ */
        //-- 品牌列表
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'list') {
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['07_merchants_brand']);


            $this->smarty->assign('full_page', 1);
            if ($adminru['ru_id'] > 0) {
                $this->smarty->assign('action_link', ['text' => $GLOBALS['_LANG']['07_brand_add'], 'href' => 'merchants_brand.php?act=add']);
            }

            $brand_list = $this->merchantsBrandManageService->getBrandList($adminru['ru_id']);

            $this->smarty->assign('brand_list', $brand_list['brand']);
            $this->smarty->assign('filter', $brand_list['filter']);
            $this->smarty->assign('record_count', $brand_list['record_count']);
            $this->smarty->assign('page_count', $brand_list['page_count']);

            $store_list = $this->storeCommonService->getCommonStoreList();
            $this->smarty->assign('store_list', $store_list);


            return $this->smarty->display('merchants_brand_list.dwt');
        }

        /*------------------------------------------------------ */
        //-- 排序、分页、查询
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'query') {
            $brand_list = $this->merchantsBrandManageService->getBrandList($adminru['ru_id']);
            $this->smarty->assign('brand_list', $brand_list['brand']);
            $this->smarty->assign('filter', $brand_list['filter']);
            $this->smarty->assign('record_count', $brand_list['record_count']);
            $this->smarty->assign('page_count', $brand_list['page_count']);

            $store_list = $this->storeCommonService->getCommonStoreList();
            $this->smarty->assign('store_list', $store_list);

            return make_json_result(
                $this->smarty->fetch('merchants_brand_list.dwt'),
                '',
                ['filter' => $brand_list['filter'], 'page_count' => $brand_list['page_count']]
            );
        }

        /*------------------------------------------------------ */
        //-- 编辑品牌
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'edit') {
            /* 权限判断 */
            admin_priv('merchants_brand');

            $brand_id = isset($_REQUEST['id']) && !empty($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
            $ubrand = isset($_REQUEST['ubrand']) && !empty($_REQUEST['ubrand']) ? intval($_REQUEST['ubrand']) : 0;

            $res = MerchantsShopBrand::select('*', 'bid as brand_id', 'brandName as brand_name', 'brandLogo as brand_logo');
            $res = $res->where('bid', $brand_id);
            $brand = $this->baseRepository->getToArrayFirst($res);

            $platform_brand_list = get_merchants_search_brand(1, 3);
            $this->smarty->assign('brand_list', $platform_brand_list);

            //关联品牌
            $link_brand = get_link_brand_list($brand['brand_id'], 3);
            $this->smarty->assign('link_brand', $link_brand);

            $this->smarty->assign('ubrand', $ubrand);
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['brand_edit']);
            $this->smarty->assign('action_link', ['text' => $GLOBALS['_LANG']['07_merchants_brand'], 'href' => 'merchants_brand.php?act=list&' . list_link_postfix()]);
            $this->smarty->assign('brand', $brand);
            $this->smarty->assign('form_action', 'updata');

            $date = ['major_brand'];
            $where = " ru_id = '" . $adminru['ru_id'] . "'";
            $major_brand = get_table_date('admin_user', $where, $date, 2);
            $this->smarty->assign('major_brand', $major_brand);

            set_default_filter(); //设置默认筛选


            return $this->smarty->display('merchants_brand_info.dwt');
        } elseif ($_REQUEST['act'] == 'updata') {
            admin_priv('merchants_brand');

            $major_business = isset($_POST['major_business']) ? intval($_POST['major_business']) : '';
            $brand_name = !empty($_POST['mer_brand_name']) ? trim($_POST['mer_brand_name']) : '';
            $brand_letter = !empty($_POST['brank_letter']) ? trim($_POST['brank_letter']) : '';
            $bid = isset($_POST['id']) && !empty($_POST['id']) ? intval($_POST['id']) : 0;
            $linkBrand = isset($_POST['link_brand']) && !empty($_POST['id']) ? intval($_POST['link_brand']) : 0;
            $ru_id = isset($_POST['ru_id']) && !empty($_POST['ru_id']) ? intval($_POST['ru_id']) : 0;
            $audit_status = isset($_POST['audit_status']) && !empty($_POST['audit_status']) ? intval($_POST['audit_status']) : 0;

            if ($brand_name != $_POST['old_brandname']) {
                /*检查品牌名是否相同*/
                $is_only = MerchantsShopBrand::where('brandName', $brand_name)->where('bid', '<>', $bid)->count();

                if ($is_only > 0) {
                    return sys_msg(sprintf($GLOBALS['_LANG']['brandname_exist'], stripslashes($brand_name)), 1);
                }
            }

            /*对描述处理*/
            if (!empty($_POST['brand_desc'])) {
                $_POST['brand_desc'] = $_POST['brand_desc'];
            }

            $is_show = isset($_REQUEST['is_show']) ? intval($_REQUEST['is_show']) : 0;

            /*处理URL*/
            $site_url = sanitize_url($_POST['site_url']);

            /*处理图片*/
            if (!empty($_FILES['brand_logo']['name'])) {
                $img_name = "data/septs_image/" . basename($image->upload_image($_FILES['brand_logo'], 'septs_image'));
            } else {
                $img_name = '';
            }

            $this->dscRepository->getOssAddFile([$img_name]);

            if (isset($_POST['link_brand']) && $adminru['ru_id'] > 0) {
                $parent['major_brand'] = $bid;

                AdminUser::where('ru_id', $adminru['ru_id'])->update($parent);
            }

            if (isset($_POST['major_business'])) {
                $param['major_business'] = $major_business;
            }

            if (isset($_POST['audit_status']) && $adminru['ru_id'] == 0) {
                $param['audit_status'] = $audit_status;

                if ($_POST['audit_status'] == 1) {
                    $brand_id = 0;
                    if (empty($linkBrand)) {
                        $brand_id = Brand::where('brand_name', $brand_name)->value('brand_id');
                        $brand_id = $brand_id ? $brand_id : 0;
                    } else {
                        $brand_id = Brand::where('brand_id', $linkBrand)->value('brand_id');
                        $brand_id = $brand_id ? $brand_id : 0;
                    }

                    if (!$brand_id) {
                        $data = [
                            'brand_name' => $brand_name,
                            'brand_letter' => $brand_letter
                        ];
                        $linkBrand = Brand::insertGetId($data);
                    }

                    if ($linkBrand > 0) {
                        $lid = LinkBrand::where('bid', $bid)->value('id');
                        $lid = $lid ? $lid : 0;

                        $link_brand = [
                            'bid' => $bid,
                            'brand_id' => $linkBrand
                        ];

                        if ($lid) {
                            if ($audit_status == 1) {
                                $goods_brand_id = LinkBrand::where('bid', $bid)->value('brand_id');
                                $goods_brand_id = $goods_brand_id ? $goods_brand_id : 0;

                                /* 更新商品的品牌编号 */
                                $data = ['brand_id' => $linkBrand];
                                Goods::where('brand_id', $goods_brand_id)->where('user_id', $ru_id)->update($data);

                                dsc_unlink(storage_public(DATA_DIR . "/sc_file/seller_brand/seller_brand_" . $ru_id));
                            }

                            //更新关联品牌
                            LinkBrand::where('bid', $bid)->update($link_brand);
                        } else {
                            //更新关联品牌
                            LinkBrand::insert($link_brand);
                        }
                    }
                }
            }

            $param['brandName'] = $brand_name;
            $param['bank_name_letter'] = $brand_letter;
            $param['site_url'] = $site_url;
            $param['brand_desc'] = $_POST['brand_desc'];
            $param['is_show'] = $is_show;
            $param['sort_order'] = $_POST['sort_order'];

            if (!empty($img_name)) {
                //有图片上传
                $param['brandLogo'] = $img_name;
            }
            $res = MerchantsShopBrand::where('bid', $bid)->update($param);
            if ($res >= 0) {
                /* 清除缓存 */
                clear_cache_files();

                admin_log($brand_name, 'edit', 'merchants_shop_brand');

                $link[0]['text'] = $GLOBALS['_LANG']['back_list'];
                $link[0]['href'] = 'merchants_brand.php?act=list&' . list_link_postfix();
                $note = vsprintf($GLOBALS['_LANG']['brandedit_succed'], $brand_name);
                return sys_msg($note, 0, $link);
            } else {
                return $this->db->error();
            }
        }

        /*------------------------------------------------------ */
        //-- 编辑品牌中文名称
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'edit_brand_name') {
            $check_auth = check_authz_json('merchants_brand');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $id = intval($_POST['id']);
            $name = json_str_iconv(trim($_POST['val']));

            /* 检查名称是否重复 */
            $res = MerchantsShopBrand::where("brandName", $name)
                ->where('bid', '<>', $id)
                ->where('user_id', $adminru['ru_id'])
                ->count();
            if ($res > 0) {
                return make_json_error(sprintf($GLOBALS['_LANG']['brandname_exist'], $name));
            } else {
                $data = ['brandName' => $name];
                $res = MerchantsShopBrand::where('bid', $id)->update($data);
                if ($res > 0) {
                    admin_log($name, 'edit', 'merchants_shop_brand');
                    return make_json_result(stripslashes($name));
                } else {
                    return make_json_result(sprintf($GLOBALS['_LANG']['brandedit_fail'], $name));
                }
            }
        }

        /*------------------------------------------------------ */
        //-- 编辑品牌英文名称
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'edit_brand_letter') {
            $check_auth = check_authz_json('brand_manage');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $id = intval($_POST['id']);
            $name = json_str_iconv(trim($_POST['val']));

            /* 检查名称是否重复 */
            $res = MerchantsShopBrand::where('bank_name_letter', $name)->where('bid', '<>', $id)->count();
            if ($res > 0) {
                return make_json_error(sprintf($GLOBALS['_LANG']['brandname_exist'], $name));
            } else {
                $data = ['bank_name_letter' => $name];
                $res = MerchantsShopBrand::where('bid', $id)->update($data);
                if ($res > 0) {
                    admin_log($name, 'edit', 'merchants_shop_brand');
                    return make_json_result(stripslashes($name));
                } else {
                    return make_json_result(sprintf($GLOBALS['_LANG']['brandedit_fail'], $name));
                }
            }
        }

        /*------------------------------------------------------ */
        //-- 编辑排序序号
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'edit_sort_order') {
            $check_auth = check_authz_json('merchants_brand');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $id = intval($_POST['id']);
            $order = intval($_POST['val']);
            $name = MerchantsShopBrand::where('bid', $id)->value('brandName');
            $name = $name ? $name : '';

            $data = ['sort_order' => $order];
            $res = MerchantsShopBrand::where('bid', $id)->update($data);
            if ($res > 0) {
                admin_log(addslashes($name), 'edit', 'merchants_shop_brand');

                return make_json_result($order);
            } else {
                return make_json_error(sprintf($GLOBALS['_LANG']['brandedit_fail'], $name));
            }
        }

        /*------------------------------------------------------ */
        //-- 切换是否显示
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'toggle_show') {
            $check_auth = check_authz_json('merchants_brand');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $id = intval($_POST['id']);
            $val = intval($_POST['val']);

            $data = ['is_show' => $val];
            MerchantsShopBrand::where('bid', $id)->update($data);
            return make_json_result($val);
        }

        /*------------------------------------------------------ */
        //-- 删除品牌 //ecmoban模板堂 --zhuo
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'remove') {
            $check_auth = check_authz_json('merchants_brand');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $id = intval($_GET['id']);
            $ru_id = MerchantsShopBrand::where('bid', $id)->value('user_id');
            $ru_id = $ru_id ? $ru_id : 0;

            $brand_id = LinkBrand::where('bid', $id)->value('brand_id');
            $brand_id = $brand_id ? $brand_id : 0;

            $this->dscRepository->getDelBatch('', $id, ['brandLogo'], 'bid', MerchantsShopBrand::whereRaw(1), 1); //删除图片

            MerchantsShopBrand::where('bid', $id)->delete();

            MerchantsShopBrandfile::where('bid', $id)->delete();

            LinkBrand::where('bid', $id)->delete();

            /* 更新商品的品牌编号 */
            $data = ['brand_id' => 0];
            Goods::where('brand_id', $brand_id)->where('user_id', $ru_id)->update($data);

            dsc_unlink(storage_public(DATA_DIR . "/sc_file/seller_brand/seller_brand_" . $ru_id));

            $url = 'merchants_brand.php?act=query&' . str_replace('act=remove', '', request()->server('QUERY_STRING'));

            return dsc_header("Location: $url\n");
        }

        /*------------------------------------------------------ */
        //-- 删除品牌图片
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'drop_logo') {
            /* 权限判断 */
            admin_priv('merchants_brand');
            $brand_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

            $this->dscRepository->getDelBatch('', $brand_id, ['brandLogo'], 'bid', MerchantsShopBrand::whereRaw(1), 1); //删除图片

            $data = ['brandLogo' => ''];
            MerchantsShopBrand::where('bid', $brand_id)->update($data);

            $link = [['text' => $GLOBALS['_LANG']['brand_edit_lnk'], 'href' => 'merchants_brand.php?act=edit&id=' . $brand_id], ['text' => $GLOBALS['_LANG']['brand_list_lnk'], 'href' => 'merchants_brand.php?act=list']];
            return sys_msg($GLOBALS['_LANG']['drop_brand_logo_success'], 0, $link);
        }
    }
}
