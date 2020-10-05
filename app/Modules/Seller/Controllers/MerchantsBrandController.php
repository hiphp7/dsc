<?php

namespace App\Modules\Seller\Controllers;

use App\Libraries\Exchange;
use App\Libraries\Image;
use App\Models\AdminUser;
use App\Models\MerchantsShopBrand;
use App\Repositories\Common\DscRepository;
use App\Services\Merchant\MerchantCommonService;
use App\Services\Store\StoreCommonService;

/**
 * 管理中心品牌管理
 */
class MerchantsBrandController extends InitController
{
    protected $dscRepository;
    protected $merchantCommonService;
    protected $storeCommonService;

    public function __construct(
        DscRepository $dscRepository,
        MerchantCommonService $merchantCommonService,
        StoreCommonService $storeCommonService
    )
    {
        $this->dscRepository = $dscRepository;
        $this->merchantCommonService = $merchantCommonService;
        $this->storeCommonService = $storeCommonService;
    }

    public function index()
    {
        $image = new Image(['bgcolor' => $GLOBALS['_CFG']['bgcolor']]);
        $menus = session('menus', '');
        $this->smarty->assign('menus', $menus);
        $this->smarty->assign('action_type', "goods");
        $exc = new Exchange($this->dsc->table("merchants_shop_brand"), $this->db, 'bid', 'brandName');

        //ecmoban模板堂 --zhuo start
        $adminru = get_admin_ru_id();
        if ($adminru['ru_id'] == 0) {
            $this->smarty->assign('priv_ru', 1);
        } else {
            $this->smarty->assign('priv_ru', 0);
        }

        $this->smarty->assign('ru_id', $adminru['ru_id']);

        $this->smarty->assign('current', basename(PHP_SELF, '.php'));
        //ecmoban模板堂 --zhuo end

        $this->smarty->assign('menu_select', ['action' => '02_cat_and_goods', 'current' => '07_merchants_brand']);
        /*------------------------------------------------------ */
        //-- 品牌列表
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'list') {
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['07_merchants_brand']);
            $this->smarty->assign('primary_cat', $GLOBALS['_LANG']['02_cat_and_goods']);

            $this->smarty->assign('full_page', 1);
            if ($adminru['ru_id'] > 0) {
                $this->smarty->assign('action_link', ['text' => $GLOBALS['_LANG']['07_brand_add'], 'href' => 'merchants_brand.php?act=add', 'class' => 'icon-plus']);
            }

            $brand_list = $this->get_brandlist($adminru['ru_id']);
            $this->smarty->assign('brand_list', $brand_list['brand']);
            $this->smarty->assign('filter', $brand_list['filter']);
            $this->smarty->assign('record_count', $brand_list['record_count']);
            $this->smarty->assign('page_count', $brand_list['page_count']);

            //分页
            $page = isset($_REQUEST['page']) && !empty(intval($_REQUEST['page'])) ? intval($_REQUEST['page']) : 1;
            $page_count_arr = seller_page($brand_list, $page);
            $this->smarty->assign('page_count_arr', $page_count_arr);

            $store_list = $this->storeCommonService->getCommonStoreList();
            $this->smarty->assign('store_list', $store_list);


            return $this->smarty->display('merchants_brand_list.dwt');
        }

        /*------------------------------------------------------ */
        //-- 排序、分页、查询
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'query') {
            $brand_list = $this->get_brandlist($adminru['ru_id']);
            $this->smarty->assign('brand_list', $brand_list['brand']);
            $this->smarty->assign('filter', $brand_list['filter']);
            $this->smarty->assign('record_count', $brand_list['record_count']);
            $this->smarty->assign('page_count', $brand_list['page_count']);

            $store_list = $this->storeCommonService->getCommonStoreList();
            $this->smarty->assign('store_list', $store_list);

            //分页
            $page = isset($_REQUEST['page']) && !empty($_REQUEST['page']) ? intval($_REQUEST['page']) : 1;
            $page_count_arr = seller_page($brand_list, $page);
            $this->smarty->assign('page_count_arr', $page_count_arr);

            return make_json_result(
                $this->smarty->fetch('merchants_brand_list.dwt'),
                '',
                ['filter' => $brand_list['filter'], 'page_count' => $brand_list['page_count']]
            );
        }

        /*------------------------------------------------------ */
        //-- 添加品牌
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'add') {
            /* 权限判断 */
            admin_priv('merchants_brand');
            $this->smarty->assign('primary_cat', $GLOBALS['_LANG']['02_cat_and_goods']);
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['07_brand_add']);
            $this->smarty->assign('action_link', ['text' => $GLOBALS['_LANG']['07_merchants_brand'], 'href' => 'merchants_brand.php?act=list', 'class' => 'icon-reply']);
            $this->smarty->assign('form_action', 'insert');

            $filter_brand_list = search_brand_list(0, 0);
            $this->smarty->assign('filter_brand_list', $filter_brand_list);


            $this->smarty->assign('brand', ['sort_order' => 50, 'is_show' => 1]);
            return $this->smarty->display('merchants_brand_info.dwt');
        }

        /*------------------------------------------------------ */
        //-- 插入品牌
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'insert') {
            /*检查品牌名是否重复*/
            admin_priv('merchants_brand');

            $is_show = isset($_REQUEST['is_show']) ? intval($_REQUEST['is_show']) : 0;
            $major_business = isset($_POST['major_business']) ? intval($_POST['major_business']) : 0;
            $linkBrand = isset($_POST['link_brand']) ? intval($_POST['link_brand']) : 0;

            $is_only = $exc->is_only('brandName', $_POST['brand_name'], 0, "user_id = '" . $adminru['ru_id'] . "'");

            if (!$is_only) {
                return sys_msg(sprintf($GLOBALS['_LANG']['brandname_exist'], stripslashes($_POST['brand_name'])), 1);
            }

            /*对描述处理*/
            if (!empty($_POST['brand_desc'])) {
                $_POST['brand_desc'] = $_POST['brand_desc'];
            }

            /*处理图片*/
            if (isset($_FILES['brand_logo'])) {
                if (!empty($_FILES['brand_logo']['name'])) {
                    $img_name = "data/septs_image/" . basename($image->upload_image($_FILES['brand_logo'], 'septs_image'));
                } else {
                    $img_name = '';
                }
            }

            $this->dscRepository->getOssAddFile([$img_name]);

            /*处理URL*/
            $site_url = isset($_POST['site_url']) ? sanitize_url($_POST['site_url']) : '';

            /*插入数据*/
            $other = [
                'user_id' => $adminru['ru_id'],
                'brandName' => addslashes($_POST['brand_name']),
                'bank_name_letter' => addslashes($_POST['brank_letter']),
                'site_url' => $site_url,
                'brand_desc' => addslashes($_POST['brand_desc']),
                'brandLogo' => $img_name,
                'is_show' => $is_show,
                'sort_order' => intval($_POST['sort_order'])
            ];

            $bid = MerchantsShopBrand::insertGetId($other);

            if (empty($linkBrand)) {
                $brand_name = trim($_POST['brand_name']);
                $brand_letter = trim($_POST['brank_letter']);
                $sql = "SELECT brand_id FROM " . $this->dsc->table('brand') . " WHERE brand_name = '$brand_name'";
                $brand_id = $this->db->getOne($sql);
                if (!$brand_id) {
                    $sql = 'INSERT INTO ' . $this->dsc->table('brand') . " (`brand_name`, `brand_letter`) VALUES ('$brand_name', '$brand_letter')";
                    $this->db->query($sql);
                    $linkBrand = $this->db->insert_id();
                }
            }

            if ($linkBrand > 0) {
                $link_brand = [
                    'bid' => $bid,
                    'brand_id' => $linkBrand
                ];
                $this->db->autoExecute($this->dsc->table('link_brand'), $link_brand, 'INSERT'); //更新关联品牌
            }

            if ($major_business > 0) {
                $parent['major_brand'] = intval($_POST['id']);
                AdminUser::where('ru_id', $adminru['ru_id'])->update($parent);
            }

            admin_log($_POST['brand_name'], 'add', 'merchants_shop_brand');

            /* 清除缓存 */
            if (cache()->has('get_seller_brand')) {
                cache()->forget('get_seller_brand');
            }

            $link[0]['text'] = $GLOBALS['_LANG']['continue_add'];
            $link[0]['href'] = 'merchants_brand.php?act=add';

            $link[1]['text'] = $GLOBALS['_LANG']['back_list'];
            $link[1]['href'] = 'merchants_brand.php?act=list';

            return sys_msg($GLOBALS['_LANG']['brandadd_succed'], 0, $link);
        }

        /*------------------------------------------------------ */
        //-- 编辑品牌
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'edit') {
            /* 权限判断 */
            admin_priv('merchants_brand');

            $brand_id = isset($_REQUEST['id']) && !empty($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
            $sql = "SELECT bid as brand_id, brandName as brand_name, bank_name_letter, site_url, brandLogo as brand_logo, brand_desc, is_show, sort_order, audit_status, major_business " .
                "FROM " . $this->dsc->table('merchants_shop_brand') . " WHERE bid = '$brand_id'";

            $brand = $this->db->GetRow($sql);

            $brand['brand_logo'] = $brand && $brand['brand_logo'] ? get_image_path($brand['brand_logo']) : '';

            $platform_brand_list = get_merchants_search_brand(1, 3);
            $this->smarty->assign('brand_list', $platform_brand_list);

            //关联品牌
            $link_brand = get_link_brand_list($brand['brand_id'], 3);
            $this->smarty->assign('link_brand', $link_brand);
            $this->smarty->assign('primary_cat', $GLOBALS['_LANG']['02_cat_and_goods']);
            $ubrand = isset($_REQUEST['ubrand']) && !empty(intval($_REQUEST['ubrand'])) ? intval($_REQUEST['ubrand']) : '';
            $this->smarty->assign('ubrand', $ubrand);
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['brand_edit']);
            $this->smarty->assign('action_link', ['text' => $GLOBALS['_LANG']['07_merchants_brand'], 'href' => 'merchants_brand.php?act=list&' . list_link_postfix(), 'class' => 'icon-reply']);
            $this->smarty->assign('brand', $brand);
            $this->smarty->assign('form_action', 'updata');

            $date = ['major_brand'];
            $where = " ru_id = '" . $adminru['ru_id'] . "'";
            $major_brand = get_table_date('admin_user', $where, $date, 2);
            $this->smarty->assign('major_brand', $major_brand);

            $this->smarty->assign('filter_brand_list', search_brand_list()); //设置品牌筛选


            return $this->smarty->display('merchants_brand_info.dwt');
        }

        /*------------------------------------------------------ */
        //-- 更新品牌
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'updata') {
            admin_priv('merchants_brand');
            $audit_status = 0;

            $major_business = isset($_POST['major_business']) ? intval($_POST['major_business']) : '';
            $linkBrand = isset($_POST['link_brand']) ? intval($_POST['link_brand']) : 0;
            $bid = isset($_POST['id']) && !empty($_POST['id']) ? intval($_POST['id']) : 0;

            if ($_POST['brand_name'] != $_POST['old_brandname']) {
                /*检查品牌名是否相同*/
                $is_only = $exc->is_only('brandName', $_POST['brand_name'], $bid, "user_id = '" . $adminru['ru_id'] . "'");

                if (!$is_only) {
                    return sys_msg(sprintf($GLOBALS['_LANG']['brandname_exist'], stripslashes($_POST['brand_name'])), 1);
                } else {
                    if ($adminru['ru_id'] > 0) {
                        $audit_status = 1;
                    }
                }
            }

            if ($_FILES['brand_logo']['name'] != '') {
                if ($adminru['ru_id'] > 0) {
                    $audit_status = 1;
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
            if (isset($_FILES['brand_logo'])) {
                if (!empty($_FILES['brand_logo']['name'])) {
                    $img_name = "data/septs_image/" . basename($image->upload_image($_FILES['brand_logo'], 'septs_image'));
                } else {
                    $img_name = '';
                }
            }

            $this->dscRepository->getOssAddFile([$img_name]);

            if (isset($_POST['link_brand']) && $adminru['ru_id'] > 0) {
                $parent['major_brand'] = $bid;
                $this->db->autoExecute($this->dsc->table('admin_user'), $parent, 'UPDTAE', 'ru_id =' . $adminru['ru_id']);
            }

            $param = [
                'brandName' => addslashes($_POST['brand_name']),
                'bank_name_letter' => addslashes($_POST['brank_letter']),
                'site_url' => $site_url,
                'brand_desc' => addslashes($_POST['brand_desc']),
                'is_show' => $is_show,
                'sort_order' => intval($_POST['sort_order'])
            ];

            if ($audit_status > 0) {
                $param['audit_status'] = 0;
            }

            if (isset($_POST['major_business'])) {
                $param['major_business'] = addslashes($major_business);
            }

            //有图片上传
            if (!empty($img_name)) {
                $param['brandLogo'] = $img_name;
            }

            MerchantsShopBrand::where('bid', $bid)->update($param);

            /* 清除缓存 */
            clear_cache_files();

            admin_log($_POST['brand_name'], 'edit', 'merchants_shop_brand');

            $brand_name = trim($_POST['brand_name']);
            $brand_letter = trim($_POST['brank_letter']);

            if (empty($_POST['link_brand'])) {
                $sql = "SELECT brand_id FROM " . $this->dsc->table('brand') . " WHERE brand_name = '$brand_name'";
            } else {
                $sql = "SELECT brand_id FROM " . $this->dsc->table('brand') . " WHERE brand_id = '$linkBrand'";
            }
            $brand_id = $this->db->getOne($sql);
            if (!$brand_id) {
                $sql = 'INSERT INTO ' . $this->dsc->table('brand') . " (`brand_name`, `brand_letter`) VALUES ('$brand_name', '$brand_letter')";
                $this->db->query($sql);
                $linkBrand = $this->db->insert_id();
            }

            if ($linkBrand > 0) {
                $sql = "SELECT id FROM " . $this->dsc->table('link_brand') . " WHERE bid= '$bid'";
                $lid = $this->db->getOne($sql);

                $link_brand = [
                    'bid' => $bid,
                    'brand_id' => $linkBrand
                ];

                if ($lid) {
                    $this->db->autoExecute($this->dsc->table('link_brand'), $link_brand, 'UPDTAE', 'bid=' . $bid); //更新关联品牌
                } else {
                    $this->db->autoExecute($this->dsc->table('link_brand'), $link_brand, 'INSERT'); //更新关联品牌
                }
            } else {
                $this->db->query(" DELETE FROM " . $this->dsc->table('link_brand') . " WHERE bid = '$bid' ");
            }

            if (cache()->has('get_seller_brand')) {
                cache()->forget('get_seller_brand');
            }

            $link[0]['text'] = $GLOBALS['_LANG']['back_list'];
            $link[0]['href'] = 'merchants_brand.php?act=list&' . list_link_postfix();
            $note = vsprintf($GLOBALS['_LANG']['brandedit_succed'], $_POST['brand_name']);
            return sys_msg($note, 0, $link);
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
            if ($exc->num("brandName", $name, $id, "user_id = '" . $adminru['ru_id'] . "'") != 0) {
                return make_json_error(sprintf($GLOBALS['_LANG']['brandname_exist'], $name));
            } else {
                if ($exc->edit("brandName = '$name'", $id)) {
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
            $check_auth = check_authz_json('merchants_brand');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $id = intval($_POST['id']);
            $name = json_str_iconv(trim($_POST['val']));

            /* 检查名称是否重复 */
            if ($exc->num("bank_name_letter", $name, $id) != 0) {
                return make_json_error(sprintf($GLOBALS['_LANG']['brandname_exist'], $name));
            } else {
                if ($exc->edit("bank_name_letter = '$name'", $id)) {
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
            $name = $exc->get_name($id);

            if ($exc->edit("sort_order = '$order'", $id)) {
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

            $exc->edit("is_show='$val'", $id);

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
            $brand_id = $this->db->getOne("SELECT brand_id FROM " . $this->dsc->table('link_brand') . " WHERE bid ='$id'", true);

            $this->dscRepository->getDelBatch('', $id, ['brandLogo'], 'bid', MerchantsShopBrand::whereRaw(1), 1); //删除图片

            $exc->drop($id, 'merchants_shop_brand', 'bid');

            $sql = 'DELETE FROM ' . $this->dsc->table('merchants_shop_brandfile') . " WHERE bid = '$id'";
            $this->db->query($sql);

            $sql = 'DELETE FROM ' . $this->dsc->table('link_brand') . " WHERE bid = '$id'";
            $this->db->query($sql);

            /* 更新商品的品牌编号 */
            $sql = "UPDATE " . $this->dsc->table('goods') . " SET brand_id = 0 WHERE brand_id = '$brand_id' AND user_id = '" . $adminru['ru_id'] . "'";
            $this->db->query($sql);

            dsc_unlink(storage_public(DATA_DIR . "/sc_file/seller_brand/seller_brand_" . $adminru['ru_id']));

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

            $sql = "UPDATE " . $this->dsc->table('merchants_shop_brand') . " SET brandLogo = '' WHERE bid = '$brand_id'";
            $this->db->query($sql);

            $link = [['text' => $GLOBALS['_LANG']['brand_edit_lnk'], 'href' => 'merchants_brand.php?act=edit&id=' . $brand_id], ['text' => $GLOBALS['_LANG']['brand_list_lnk'], 'href' => 'merchants_brand.php?act=list']];
            return sys_msg($GLOBALS['_LANG']['drop_brand_logo_success'], 0, $link);
        }
    }

    /**
     * 获取品牌列表
     *
     * @access  public
     * @return  array
     */
    private function get_brandlist($ru_id)
    {
        $where = '';
        if ($ru_id > 0) {
            $where .= " and user_id = '$ru_id'";
        }

        $result = get_filter();
        if ($result === false) {
            /* 分页大小 */
            $filter = [];

            $filter['sort_by'] = empty($_REQUEST['sort_by']) ? 'msb.bid' : trim($_REQUEST['sort_by']);
            $filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'DESC' : trim($_REQUEST['sort_order']);
            $brand_name = isset($_POST['brand_name']) ? trim($_POST['brand_name']) : '';

            //管理员查询的权限 -- 店铺查询 start
            $filter['store_search'] = empty($_REQUEST['store_search']) ? 0 : intval($_REQUEST['store_search']);
            $filter['merchant_id'] = isset($_REQUEST['merchant_id']) ? intval($_REQUEST['merchant_id']) : 0;
            $filter['store_keyword'] = isset($_REQUEST['store_keyword']) ? trim($_REQUEST['store_keyword']) : '';

            $store_where = '';
            $store_search_where = '';
            if ($filter['store_search'] != 0) {
                if ($ru_id == 0) {
                    $store_type = isset($_REQUEST['store_type']) && !empty($_REQUEST['store_type']) ? intval($_REQUEST['store_type']) : 0;

                    if ($store_type) {
                        $store_search_where = "AND msi.shopNameSuffix = '$store_type'";
                    }

                    if ($filter['store_search'] == 1) {
                        $where .= " AND msb.user_id = '" . $filter['merchant_id'] . "' ";
                    } elseif ($filter['store_search'] == 2) {
                        $store_where .= " AND msi.rz_shopName LIKE '%" . mysql_like_quote($filter['store_keyword']) . "%'";
                    } elseif ($filter['store_search'] == 3) {
                        $store_where .= " AND msi.shoprz_brandName LIKE '%" . mysql_like_quote($filter['store_keyword']) . "%' " . $store_search_where;
                    }

                    if ($filter['store_search'] > 1) {
                        $where .= " AND (SELECT msi.user_id FROM " . $this->dsc->table('merchants_shop_information') . ' as msi ' .
                            " WHERE msi.user_id = msb.user_id $store_where) > 0 ";
                    }
                }
            }
            //管理员查询的权限 -- 店铺查询 end

            /* 记录总数以及页数 */
            if (!empty($brand_name)) {
                $sql = "SELECT COUNT(*) FROM " . $this->dsc->table('merchants_shop_brand') . " AS msb " . " WHERE msb.brandName LIKE '%" . mysql_like_quote($brand_name) . "%'" . $where;
            } else {
                $sql = "SELECT COUNT(*) FROM " . $this->dsc->table('merchants_shop_brand') . " AS msb " . " where 1 " . $where;
            }

            $filter['record_count'] = $this->db->getOne($sql);

            $filter = page_and_size($filter);

            /* 查询记录 */
            if (!empty($brand_name)) {
                if (strtoupper(EC_CHARSET) == 'GBK') {
                    $keyword = iconv("UTF-8", "gb2312", $brand_name);
                } else {
                    $keyword = $brand_name;
                }

                $sql = "SELECT msb.* FROM " . $this->dsc->table('merchants_shop_brand') . " AS msb " . " WHERE msb.brandName like '%{$keyword}%' " . $where . " ORDER BY $filter[sort_by] $filter[sort_order]";
            } else {
                $sql = "SELECT msb.* FROM " . $this->dsc->table('merchants_shop_brand') . " AS msb " . " where 1 " . $where . " ORDER BY $filter[sort_by] $filter[sort_order]";
            }

            set_filter($filter, $sql);
        } else {
            $sql = $result['sql'];
            $filter = $result['filter'];
        }
        $res = $this->db->selectLimit($sql, $filter['page_size'], $filter['start'] ?? 0);

        $arr = [];
        foreach ($res as $rows) {
            $site_url = empty($rows['site_url']) ? 'N/A' : '<a href="' . $rows['site_url'] . '" target="_brank">' . $rows['site_url'] . '</a>';
            $rows['site_url'] = $site_url;
            $rows['brand_logo'] = get_image_path($rows['brandLogo']);
            $rows['brand_id'] = $rows['bid'];
            $rows['brand_name'] = $rows['brandName'];
            $rows['brand_letter'] = $rows['bank_name_letter'];
            $rows['user_name'] = $this->merchantCommonService->getShopName($rows['user_id'], 1);
            $rows['link_brand'] = get_link_brand_list($rows['bid'], 3);

            $arr[] = $rows;
        }

        return ['brand' => $arr, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']];
    }
}
