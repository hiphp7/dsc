<?php

namespace App\Modules\Admin\Controllers;

use App\Models\Attribute;
use App\Models\Goods;
use App\Models\GoodsAttr;
use App\Models\GoodsType;
use App\Models\GoodsTypeCat;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\DscRepository;
use App\Services\Goods\GoodsTypeManageService;
use App\Services\Merchant\MerchantCommonService;
use App\Services\Store\StoreCommonService;

/**
 * 商品类型管理程序
 */
class GoodsTypeController extends InitController
{
    protected $merchantCommonService;
    protected $baseRepository;
    protected $goodsTypeManageService;
    protected $dscRepository;
    protected $storeCommonService;

    public function __construct(
        MerchantCommonService $merchantCommonService,
        BaseRepository $baseRepository,
        GoodsTypeManageService $goodsTypeManageService,
        DscRepository $dscRepository,
        StoreCommonService $storeCommonService
    )
    {
        $this->merchantCommonService = $merchantCommonService;
        $this->baseRepository = $baseRepository;
        $this->goodsTypeManageService = $goodsTypeManageService;
        $this->dscRepository = $dscRepository;
        $this->storeCommonService = $storeCommonService;
    }

    public function index()
    {
        //ecmoban模板堂 --zhuo start
        $adminru = get_admin_ru_id();
        if ($adminru['ru_id'] == 0) {
            $this->smarty->assign('priv_ru', 1);
        } else {
            $this->smarty->assign('priv_ru', 0);
        }
        //ecmoban模板堂 --zhuo end

        /*------------------------------------------------------ */
        //-- 管理界面
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'manage') {
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['08_goods_type']);
            $this->smarty->assign('full_page', 1);

            $good_type_list = $this->goodsTypeManageService->getGoodsType($adminru['ru_id']);
            $good_in_type = '';

            $this->smarty->assign('goods_type_arr', $good_type_list['type']);
            $this->smarty->assign('filter', $good_type_list['filter']);
            $this->smarty->assign('record_count', $good_type_list['record_count']);
            $this->smarty->assign('page_count', $good_type_list['page_count']);

            $res = Attribute::whereHas('getGoodsAttr')
                ->groupBy('cat_id')->orderBy('sort_order')->orderBy('attr_id');
            $query = $this->baseRepository->getToArrayGet($res);

            foreach ($query as $row) {
                if ($row['cat_id']) {
                    $good_in_type[$row['cat_id']] = 1;
                }
            }
            $this->smarty->assign('good_in_type', $good_in_type);

            //ecmoban模板堂 --zhuo start
            if ($GLOBALS['_CFG']['attr_set_up'] == 0) {
                if ($adminru['ru_id'] == 0) {
                    $this->smarty->assign('action_link', ['text' => $GLOBALS['_LANG']['new_goods_type'], 'href' => 'goods_type.php?act=add']);
                    $this->smarty->assign('attr_set_up', 1);
                } else {
                    $this->smarty->assign('attr_set_up', 0);
                }
            } elseif ($GLOBALS['_CFG']['attr_set_up'] == 1) {
                $this->smarty->assign('action_link', ['text' => $GLOBALS['_LANG']['new_goods_type'], 'href' => 'goods_type.php?act=add']);
                $this->smarty->assign('attr_set_up', 1);
            }
            //ecmoban模板堂 --zhuo end

            //属性分类
            $this->smarty->assign('action_link1', ['text' => $GLOBALS['_LANG']['type_cart'], 'href' => 'goods_type.php?act=cat_list']);
            $this->smarty->assign('action_link2', ['text' => $GLOBALS['_LANG']['08_goods_type'], 'href' => 'goods_type.php?act=manage']);
            $this->smarty->assign('act_type', $_REQUEST['act']);

            //区分自营和店铺
            self_seller(basename(request()->getRequestUri()), 'manage');

            $store_list = $this->storeCommonService->getCommonStoreList();
            $this->smarty->assign('store_list', $store_list);

            return $this->smarty->display('goods_type.dwt');
        }

        /*------------------------------------------------------ */
        //-- 获得列表
        /*------------------------------------------------------ */

        elseif ($_REQUEST['act'] == 'query') {
            $good_type_list = $this->goodsTypeManageService->getGoodsType($adminru['ru_id']);

            //ecmoban模板堂 --zhuo start
            if ($GLOBALS['_CFG']['attr_set_up'] == 0) {
                if ($adminru['ru_id'] == 0) {
                    $this->smarty->assign('attr_set_up', 1);
                } else {
                    $this->smarty->assign('attr_set_up', 0);
                }
            } elseif ($GLOBALS['_CFG']['attr_set_up'] == 1) {
                $this->smarty->assign('attr_set_up', 1);
            }
            //ecmoban模板堂 --zhuo end

            $this->smarty->assign('goods_type_arr', $good_type_list['type']);
            $this->smarty->assign('filter', $good_type_list['filter']);
            $this->smarty->assign('record_count', $good_type_list['record_count']);
            $this->smarty->assign('page_count', $good_type_list['page_count']);

            return make_json_result(
                $this->smarty->fetch('goods_type.dwt'),
                '',
                ['filter' => $good_type_list['filter'], 'page_count' => $good_type_list['page_count']]
            );
        }
        /*------------------------------------------------------ */
        //-- 属性分类
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'cat_list') {
            admin_priv('goods_type');
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['type_cart']);
            $this->smarty->assign('full_page', 1);
            $level = empty($_REQUEST['level']) ? 1 : intval($_REQUEST['level']) + 1;

            $good_type_cat = get_typecat($level);

            $this->smarty->assign('goods_type_arr', $good_type_cat['type']);
            $this->smarty->assign('filter', $good_type_cat['filter']);
            $this->smarty->assign('record_count', $good_type_cat['record_count']);
            $this->smarty->assign('page_count', $good_type_cat['page_count']);
            //属性分类
            $this->smarty->assign('action_link', ['text' => $GLOBALS['_LANG']['type_cart_add'], 'href' => 'goods_type.php?act=cat_add']);
            $this->smarty->assign('action_link1', ['text' => $GLOBALS['_LANG']['type_cart'], 'href' => 'goods_type.php?act=cat_list']);
            $this->smarty->assign('action_link2', ['text' => $GLOBALS['_LANG']['08_goods_type'], 'href' => 'goods_type.php?act=manage']);
            $this->smarty->assign('act_type', $_REQUEST['act']);
            $this->smarty->assign('level', $level);
            //区分自营和店铺
            self_seller(basename(request()->getRequestUri()), 'cat_list');

            return $this->smarty->display('goods_type_cat.dwt');
        }
        /*------------------------------------------------------ */
        //-- 属性分类AJAX
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'cat_list_query') {
            $check_auth = check_authz_json('goods_type');
            if ($check_auth !== true) {
                return $check_auth;
            }
            $level = empty($_REQUEST['level']) ? 1 : intval($_REQUEST['level']);
            $good_type_cat = get_typecat($level);
            $this->smarty->assign('goods_type_arr', $good_type_cat['type']);
            $this->smarty->assign('filter', $good_type_cat['filter']);
            $this->smarty->assign('record_count', $good_type_cat['record_count']);
            $this->smarty->assign('page_count', $good_type_cat['page_count']);
            $this->smarty->assign('level', $level);

            return make_json_result(
                $this->smarty->fetch('goods_type_cat.dwt'),
                '',
                ['filter' => $good_type_cat['filter'], 'page_count' => $good_type_cat['page_count']]
            );
        }
        /*------------------------------------------------------ */
        //-- 属性分类添加
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'cat_add' || $_REQUEST['act'] == 'cat_edit') {
            admin_priv('goods_type');

            if ($_REQUEST['act'] == 'cat_add') {
                $this->smarty->assign('ur_here', $GLOBALS['_LANG']['type_cart_add']);
                $this->smarty->assign("form_act", "cat_insert");
            } else {
                $this->smarty->assign('ur_here', $GLOBALS['_LANG']['type_cart_edit']);
                $this->smarty->assign("form_act", "cat_update");
            }

            $this->smarty->assign('action_link', ['text' => $GLOBALS['_LANG']['type_cart'], 'href' => 'goods_type.php?act=cat_list']);

            $cat_id = !empty($_REQUEST['cat_id']) ? intval($_REQUEST['cat_id']) : 0;

            $parent = get_every_category($cat_id, 'goods_type_cat'); //获取上一级
            $this->smarty->assign("parent", $parent);

            //获取全部一级的分类
            $act = ($_REQUEST['act'] == 'cat_add') ? 'insert' : 'update';
            $this->smarty->assign('act', $act);

            //获取全部一级的分类
            if ($cat_id > 0) {
                $res = GoodsTypeCat::where('cat_id', $cat_id);
                $type_cat = $this->baseRepository->getToArrayFirst($res);

                $cat_tree = get_type_cat_arr($type_cat['parent_id'], 2);
                $this->smarty->assign("cat_tree", $cat_tree);

                if ($type_cat && isset($cat_tree['arr']) && $cat_tree['arr'] && $type_cat['parent_id'] > 0) {
                    $type_cat['child_parent_id'] = GoodsTypeCat::where('cat_id', $type_cat['parent_id'])->value('parent_id');
                } else {
                    $type_cat['child_parent_id'] = $type_cat['child_parent_id'] ?? 0;
                }

                $this->smarty->assign("type_cat", $type_cat);
                $ru_id = $type_cat['user_id'];
            } else {
                $ru_id = 0;
            }

            $cat_level = get_type_cat_arr(0, 0, 0, $ru_id);
            $this->smarty->assign("cat_level", $cat_level);
            return $this->smarty->display('goods_type_cat_info.dwt');
        }
        /*------------------------------------------------------ */
        //-- 属性分类�        �库
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'cat_insert' || $_REQUEST['act'] == 'cat_update') {
            $cat_name = !empty($_REQUEST['cat_name']) ? trim($_REQUEST['cat_name']) : '';
            $parent_id = !empty($_REQUEST['attr_parent_id']) ? intval($_REQUEST['attr_parent_id']) : 0;
            $sort_order = !empty($_REQUEST['sort_order']) ? intval($_REQUEST['sort_order']) : 50;
            $cat_id = !empty($_REQUEST['cat_id']) ? intval($_REQUEST['cat_id']) : 0;
            $seller_list = isset($_REQUEST['seller_list']) && !empty($_REQUEST['seller_list']) ? 1 : 0;  //商家和自营订单标识

            //获取入库分类的层级
            if ($parent_id > 0) {
                $res = GoodsTypeCat::where('cat_id', $parent_id);
                $parent_cat = $this->baseRepository->getToArrayFirst($res);

                $level = ($parent_cat['level']) + 1;
            } else {
                $level = 1;
            }

            $parent_cat['cat_id'] = $parent_cat['cat_id'] ?? 0;
            $parent_cat['level'] = $parent_cat['level'] ?? 0;
            $parent_cat['parent_id'] = $parent_cat['parent_id'] ?? 0;
            $parent_cat['user_id'] = $parent_cat['user_id'] ?? 0;

            //处理入库数组
            $cat_info = [
                'cat_name' => $cat_name,
                'parent_id' => $parent_id,
                'sort_order' => $sort_order,
                'user_id' => $parent_cat['user_id']
            ];

            $cat_info['level'] = $level;

            if ($_REQUEST['act'] == 'cat_insert') {

                /*检查是否重复*/
                $is_only = GoodsTypeCat::where('cat_name', $cat_name)->where('user_id', $adminru['ru_id'])->count();
                if ($is_only > 0) {
                    return sys_msg(sprintf($GLOBALS['_LANG']['exist_cat'], stripslashes($cat_name)), 1);
                }

                GoodsTypeCat::insert($cat_info);
                $link[0]['text'] = $GLOBALS['_LANG']['continue_add'];
                $link[0]['href'] = 'goods_type.php?act=cat_add&cat_id=' . $parent_id . '&seller_list=' . $seller_list;

                $link[1]['text'] = $GLOBALS['_LANG']['back_list'];

                if ($parent_cat['parent_id'] > 0) {
                    $link[1]['href'] = 'goods_type.php?act=cat_list&parent_id=' . $parent_cat['parent_id'] . '&level=' . ($parent_cat['level'] - 1) . '&seller_list=' . $seller_list;
                } else {
                    $link[1]['href'] = 'goods_type.php?act=cat_list&seller_list=' . $seller_list;
                }

                return sys_msg($GLOBALS['_LANG']['add_succeed'], 0, $link);
            } else {


                /*检查是否重复*/
                $is_only = GoodsTypeCat::where('cat_name', $cat_name)
                    ->where('user_id', $adminru['ru_id'])
                    ->where('cat_id', '<>', $cat_id)
                    ->count();
                if ($is_only > 0) {
                    return sys_msg(sprintf($GLOBALS['_LANG']['exist_cat'], stripslashes($cat_name)), 1);
                }

                GoodsTypeCat::where('cat_id', $cat_id)->update($cat_info);
                $link[0]['text'] = $GLOBALS['_LANG']['back_list'];

                $link[0]['href'] = 'goods_type.php?act=cat_list&parent_id=' . $parent_cat['cat_id'] . '&level=' . $parent_cat['level'] . '&seller_list=' . $seller_list;

                return sys_msg($GLOBALS['_LANG']['edit_succeed'], 0, $link);
            }
        }
        /*------------------------------------------------------ */
        //-- 删除类型分类
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'remove_cat') {
            $check_auth = check_authz_json('goods_type');
            if ($check_auth !== true) {
                return $check_auth;
            }
            $id = intval($_GET['id']);

            //判断是否存在下级
            $cat_count = GoodsTypeCat::where('parent_id', $id)->count();

            //判断分类下是否存在类型
            $type_count = GoodsType::where('c_id', $id)->count();

            //如果存在下级 ，或者分类下存在类型，则不能删除
            if ($cat_count > 0 || $type_count > 0) {
                return make_json_error($GLOBALS['_LANG']['remove_prompt']);
            } else {
                GoodsTypeCat::where('cat_id', $id)->delete();
            }

            $url = 'goods_type.php?act=cat_list_query&' . str_replace('act=remove_cat', '', request()->server('QUERY_STRING'));
            return dsc_header("Location: $url\n");
        }
        /*------------------------------------------------------ */
        //-- 编辑排序序号
        /*------------------------------------------------------ */

        if ($_REQUEST['act'] == 'edit_sort_order') {
            $check_auth = check_authz_json('goods_type');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $id = intval($_POST['id']);
            $val = intval($_POST['val']);

            $data = ['sort_order' => $val];
            GoodsTypeCat::where('cat_id', $id)->update($data);
            clear_cache_files();

            return make_json_result($val);
        }
        /*------------------------------------------------------ */
        //-- 修改商品类型名称
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'edit_type_name') {
            $check_auth = check_authz_json('goods_type');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $type_id = !empty($_POST['id']) ? intval($_POST['id']) : 0;
            $type_name = !empty($_POST['val']) ? json_str_iconv(trim($_POST['val'])) : '';

            /* 检查名称是否重复 */
            $is_only = GoodsType::where('cat_name', $type_name)->where('cat_id', '<>', $type_id)->count();

            if ($is_only < 1) {
                $data = ['cat_name' => $type_name];
                GoodsType::where('cat_id', $type_id)->update($data);

                admin_log($type_name, 'edit', 'goods_type');

                return make_json_result(stripslashes($type_name));
            } else {
                return make_json_error($GLOBALS['_LANG']['repeat_type_name']);
            }
        }

        /*------------------------------------------------------ */
        //-- 切换启用状态
        /*------------------------------------------------------ */

        elseif ($_REQUEST['act'] == 'toggle_enabled') {
            $check_auth = check_authz_json('goods_type');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $id = intval($_POST['id']);
            $val = intval($_POST['val']);

            $data = ['enabled' => $val];
            GoodsType::where('cat_id', $id)->update($data);

            return make_json_result($val);
        }

        /*------------------------------------------------------ */
        //-- 添加商品类型
        /*------------------------------------------------------ */

        elseif ($_REQUEST['act'] == 'add') {
            admin_priv('goods_type');

            //ecmoban模板堂 --zhuo start
            if ($GLOBALS['_CFG']['attr_set_up'] == 0) {
                if ($adminru['ru_id'] > 0) {
                    $links = [['href' => 'goods_type.php?act=manage', 'text' => $GLOBALS['_LANG']['back_list']]];
                    return sys_msg($GLOBALS['_LANG']['temporary_not_attr_power'], 0, $links);
                }
            }
            //ecmoban模板堂 --zhuo end
            $cat_level = get_type_cat_arr();
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['new_goods_type']);
            $this->smarty->assign('action_link', ['href' => 'goods_type.php?act=manage', 'text' => $GLOBALS['_LANG']['goods_type_list']]);
            $this->smarty->assign('action', 'add');
            $this->smarty->assign('form_act', 'insert');
            $this->smarty->assign('goods_type', ['enabled' => 1]);
            $this->smarty->assign('cat_level', $cat_level);


            return $this->smarty->display('goods_type_info.dwt');
        } elseif ($_REQUEST['act'] == 'insert') {
            $parent_id = !empty($_REQUEST['attr_parent_id']) ? intval($_REQUEST['attr_parent_id']) : 0;
            $goods_type['cat_name'] = $this->dscRepository->subStr($_POST['cat_name'], 60);
            $goods_type['attr_group'] = $this->dscRepository->subStr($_POST['attr_group'], 255);
            $goods_type['enabled'] = intval($_POST['enabled']);
            $goods_type['c_id'] = $parent_id;
            $goods_type['user_id'] = $adminru['ru_id'];

            /* 检查名称是否重复 */
            $is_only = GoodsType::where('cat_name', $goods_type['cat_name'])->count();

            if ($is_only > 0) {
                return sys_msg($GLOBALS['_LANG']['repeat_type_name'], 1);
            }

            $id = GoodsType::insertGetId($goods_type);

            if ($id) {
                $links = [['href' => 'goods_type.php?act=manage', 'text' => $GLOBALS['_LANG']['back_list']]];
                return sys_msg($GLOBALS['_LANG']['add_goodstype_success'], 0, $links);
            } else {
                return sys_msg($GLOBALS['_LANG']['add_goodstype_failed'], 1);
            }
        }

        /*------------------------------------------------------ */
        //-- 编辑商品类型
        /*------------------------------------------------------ */

        elseif ($_REQUEST['act'] == 'edit') {
            $goods_type = $this->goodsTypeManageService->getGoodstypeInfo(intval($_GET['cat_id']));

            if (empty($goods_type)) {
                return sys_msg($GLOBALS['_LANG']['cannot_found_goodstype'], 1);
            }

            admin_priv('goods_type');

            //ecmoban模板堂 --zhuo start
            if ($GLOBALS['_CFG']['attr_set_up'] == 0) {
                if ($adminru['ru_id'] > 0) {
                    $links = [['href' => 'goods_type.php?act=manage', 'text' => $GLOBALS['_LANG']['back_list']]];
                    return sys_msg($GLOBALS['_LANG']['temporary_not_attr_power'], 0, $links);
                }
            }
            //ecmoban模板堂 --zhuo end
            //获取分类数组
            $cat_level = get_type_cat_arr(0, 0, 0, $goods_type['user_id']);
            $this->smarty->assign('cat_level', $cat_level);
            $cat_tree = get_type_cat_arr($goods_type['c_id'], 2, 0, $goods_type['user_id']);
            $cat_tree1 = ['checked_id' => $cat_tree['checked_id']];
            if ($cat_tree['checked_id'] > 0) {
                $cat_tree1 = get_type_cat_arr($cat_tree['checked_id'], 2, 0, $goods_type['user_id']);
            }
            $this->smarty->assign("cat_tree", $cat_tree);
            $this->smarty->assign("cat_tree1", $cat_tree1);
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['edit_goods_type']);
            $this->smarty->assign('action_link', ['href' => 'goods_type.php?act=manage', 'text' => $GLOBALS['_LANG']['goods_type_list']]);
            $this->smarty->assign('action', 'add');
            $this->smarty->assign('form_act', 'update');
            $this->smarty->assign('goods_type', $goods_type);


            return $this->smarty->display('goods_type_info.dwt');
        } elseif ($_REQUEST['act'] == 'update') {
            $parent_id = !empty($_REQUEST['attr_parent_id']) ? intval($_REQUEST['attr_parent_id']) : 0;
            $goods_type['c_id'] = $parent_id;
            $goods_type['cat_name'] = $this->dscRepository->subStr($_POST['cat_name'], 60);
            $goods_type['attr_group'] = $this->dscRepository->subStr($_POST['attr_group'], 255);
            $goods_type['enabled'] = intval($_POST['enabled']);
            $cat_id = intval($_POST['cat_id']);
            $old_groups = get_attr_groups($cat_id);

            $res = GoodsType::where('cat_id', $cat_id)->update($goods_type);
            if ($res > 0) {
                /* 对比原来的分组 */
                $new_groups = explode("\n", str_replace("\r", '', $goods_type['attr_group']));  // 新的分组

                foreach ($old_groups as $key => $val) {
                    $found = array_search($val, $new_groups);

                    if ($found === null || $found === false) {
                        /* 老的分组没有在新的分组中找到 */
                        $this->goodsTypeManageService->updateAttributeGroup($cat_id, $key, 0);
                    } else {
                        /* 老的分组出现在新的分组中了 */
                        if ($key != $found) {
                            $this->goodsTypeManageService->updateAttributeGroup($cat_id, $key, $found); // 但是分组的key变了,需要更新属性的分组
                        }
                    }
                }

                $links = [['href' => 'goods_type.php?act=manage', 'text' => $GLOBALS['_LANG']['back_list']]];
                return sys_msg($GLOBALS['_LANG']['edit_goodstype_success'], 0, $links);
            } else {
                return sys_msg($GLOBALS['_LANG']['edit_goodstype_failed'], 1);
            }
        }

        /*------------------------------------------------------ */
        //-- 删除商品类型
        /*------------------------------------------------------ */

        elseif ($_REQUEST['act'] == 'remove') {
            $check_auth = check_authz_json('goods_type');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $id = intval($_GET['id']);

            $name = GoodsType::where('cat_id', $id)->value('cat_name');
            $name = $name ? $name : '';

            $res = GoodsType::where('cat_id', $id)->delete();
            if ($res > 0) {
                admin_log(addslashes($name), 'remove', 'goods_type');

                /* 清除该类型下的所有属性 */
                $res = Attribute::select('attr_id')->where('cat_id', $id);
                $arr = $this->baseRepository->getToArrayGet($res);
                $arr = $this->baseRepository->getFlatten($arr);

                Attribute::whereIn('attr_id', $arr)->delete();
                GoodsAttr::whereIn('attr_id', $arr)->delete();

                $url = 'goods_type.php?act=query&' . str_replace('act=remove', '', request()->server('QUERY_STRING'));

                return dsc_header("Location: $url\n");
            } else {
                return make_json_error($GLOBALS['_LANG']['remove_failed']);
            }
        } //获取下级分类
        elseif ($_REQUEST['act'] == 'get_childcat') {
            $result = ['content' => '', 'error' => ''];

            $cat_id = !empty($_REQUEST['cat_id']) ? intval($_REQUEST['cat_id']) : 0;
            $level = !empty($_REQUEST['level']) ? intval($_REQUEST['level']) + 1 : 0;
            $type = !empty($_REQUEST['type']) ? intval($_REQUEST['type']) : 0;
            $typeCat = !empty($_REQUEST['typeCat']) ? intval($_REQUEST['typeCat']) : 0;
            $child_cat = get_type_cat_arr($cat_id);
            if (!empty($child_cat)) {
                $result['error'] = 0;
                $this->smarty->assign('child_cat', $child_cat);
                $this->smarty->assign('level', $level);
                $this->smarty->assign('type', $type);
                $this->smarty->assign('typeCat', $typeCat);
                $result['content'] = $this->smarty->fetch("library/type_cat.lbi");
            } else {
                $result['error'] = 1;
            }
            return response()->json($result);
        } //获取指定分类下的类型
        elseif ($_REQUEST['act'] == 'get_childtype') {
            $result = ['content' => '', 'error' => ''];

            $goods_id = !empty($_REQUEST['goods_id']) ? intval($_REQUEST['goods_id']) : 0;
            $cat_id = !empty($_REQUEST['cat_id']) ? intval($_REQUEST['cat_id']) : 0;
            $typeCat = !empty($_REQUEST['typeCat']) ? intval($_REQUEST['typeCat']) : 0;
            $res = GoodsType::whereRaw(1);

            if ($goods_id > 0) {
                $user_id = Goods::where('goods_id', $goods_id)->value('user_id');
                $user_id = $user_id ? $user_id : 0;
                if ($user_id > 0) {
                    $res = $res->where('user_id', $user_id);
                }
            } else {
                $res = $res->where('user_id', $adminru['ru_id']);
            }

            if ($cat_id > 0) {
                $cat_keys = get_type_cat_arr($cat_id, 1, 1);//获取指定分类下的所有下级分类

                $cat_keys = $this->baseRepository->getExplode($cat_keys);
                $res = $res->where('c_id', '<>', 0)->whereIn('c_id', $cat_keys);
            }
            $type_list = $this->baseRepository->getToArrayGet($res);
            //获取分类数组下的所有类型
            $result['error'] = 0;
            $this->smarty->assign('goods_type_list', $type_list);
            $this->smarty->assign('type_html', 1);
            $this->smarty->assign('typeCat', $typeCat);
            $result['content'] = $this->smarty->fetch("library/type_cat.lbi");

            return response()->json($result);
        }
    }
}
