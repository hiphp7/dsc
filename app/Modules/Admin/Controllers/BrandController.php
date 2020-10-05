<?php

namespace App\Modules\Admin\Controllers;

use App\Libraries\Image;
use App\Libraries\Pinyin;
use App\Models\Brand;
use App\Models\BrandExtend;
use App\Models\CollectBrand;
use App\Models\FavourableActivity;
use App\Models\Goods;
use App\Models\ShopConfig;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\DscRepository;
use App\Repositories\Common\PinyinRepository;
use App\Services\Brand\BrandManageService;
use Illuminate\Support\Facades\DB;

/**
 * 管理中心品牌管理
 */
class BrandController extends InitController
{
    protected $brandManageService;
    protected $baseRepository;
    protected $config;
    protected $dscRepository;
    protected $pinyinRepository;

    public function __construct(
        BrandManageService $brandManageService,
        BaseRepository $baseRepository,
        DscRepository $dscRepository,
        PinyinRepository $pinyinRepository
    )
    {
        $this->brandManageService = $brandManageService;
        $this->baseRepository = $baseRepository;
        $this->dscRepository = $dscRepository;
        $this->config = $this->dscRepository->dscConfig();
        $this->pinyinRepository = $pinyinRepository;
    }

    public function index()
    {
        $image = new Image(['bgcolor' => $this->config['bgcolor']]);

        $this->smarty->assign('menu_select', ['action' => '02_cat_and_goods', 'current' => '06_goods_brand_list']);

        /*------------------------------------------------------ */
        //-- 品牌列表
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'list') {
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['06_goods_brand_list']);
            $this->smarty->assign('action_link', ['text' => $GLOBALS['_LANG']['07_brand_add'], 'href' => 'brand.php?act=add']);
            $this->smarty->assign('full_page', 1);

            $brand_list = $this->brandManageService->getBrandList();

            $this->smarty->assign('brand_list', $brand_list['brand']);
            $this->smarty->assign('filter', $brand_list['filter']);
            $this->smarty->assign('record_count', $brand_list['record_count']);
            $this->smarty->assign('page_count', $brand_list['page_count']);


            return $this->smarty->display('brand_list.dwt');
        }

        /*------------------------------------------------------ */
        //-- 排序、分页、查询
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'query') {
            $brand_list = $this->brandManageService->getBrandList();
            $this->smarty->assign('brand_list', $brand_list['brand']);
            $this->smarty->assign('filter', $brand_list['filter']);
            $this->smarty->assign('record_count', $brand_list['record_count']);
            $this->smarty->assign('page_count', $brand_list['page_count']);

            return make_json_result(
                $this->smarty->fetch('brand_list.dwt'),
                '',
                ['filter' => $brand_list['filter'], 'page_count' => $brand_list['page_count']]
            );
        }

        /*------------------------------------------------------ */
        //-- 添加品牌
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'add') {
            /* 权限判断 */
            admin_priv('brand_manage');

            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['07_brand_add']);
            $this->smarty->assign('action_link', ['text' => $GLOBALS['_LANG']['06_goods_brand_list'], 'href' => 'brand.php?act=list']);
            $this->smarty->assign('form_action', 'insert');
            $this->smarty->assign('is_need', 1);


            $this->smarty->assign('brand', ['sort_order' => 50, 'is_show' => 1]);
            return $this->smarty->display('brand_info.dwt');
        }

        /*------------------------------------------------------ */
        //-- 插入品牌
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'insert') {
            /*检查品牌名是否重复*/
            admin_priv('brand_manage');

            $is_show = isset($_REQUEST['is_show']) ? intval($_REQUEST['is_show']) : 0;
            $brand_name = isset($_POST['brand_name']) && !empty($_POST['brand_name']) ? addslashes($_POST['brand_name']) : '';
            $name_pinyin = isset($_POST['name_pinyin']) && !empty($_POST['name_pinyin']) ? addslashes($_POST['name_pinyin']) : '';

            $is_only = Brand::where('brand_name', $brand_name)->count();
            if ($is_only > 0) {
                return sys_msg(sprintf($GLOBALS['_LANG']['brandname_exist'], stripslashes($brand_name)), 1);
            }

            /*对描述处理*/
            if (!empty($_POST['brand_desc'])) {
                $_POST['brand_desc'] = $_POST['brand_desc'];
            }

            $brand_image = [];

            /*处理图片*/
            if (isset($_FILES['brand_logo'])) {
                $img_name = basename($image->upload_image($_FILES['brand_logo'], 'brandlogo'));

                if ($img_name) {
                    $brand_image[] = DATA_DIR . '/brandlogo/' . $img_name;
                }
            }

            if (isset($_FILES['index_img'])) {
                $index_img = basename($image->upload_image($_FILES['index_img'], 'indeximg'));

                if ($index_img) {
                    $brand_image[] = DATA_DIR . '/indeximg/' . $index_img;
                }
            }

            if (isset($_FILES['brand_bg'])) {
                $brand_bg = basename($image->upload_image($_FILES['brand_bg'], 'brandbg'));

                if ($brand_bg) {
                    $brand_image[] = DATA_DIR . '/indeximg/' . $brand_bg;
                }
            }

            $this->dscRepository->getOssAddFile($brand_image);

            /*处理URL*/
            $site_url = isset($_POST['site_url']) && !empty($_POST['site_url']) ? sanitize_url($_POST['site_url']) : '';
            $brand_letter = isset($_POST['brand_letter']) && !empty($_POST['brand_letter']) ? addslashes($_POST['brand_letter']) : '';
            $brand_first_char = isset($_POST['brand_first_char']) && !empty($_POST['brand_first_char']) ? addslashes($_POST['brand_first_char']) : '';
            $brand_desc = isset($_POST['brand_desc']) && !empty($_POST['brand_desc']) ? addslashes($_POST['brand_desc']) : '';
            $sort_order = isset($_POST['sort_order']) && !empty($_POST['sort_order']) ? intval($_POST['sort_order']) : '';

            if (empty($name_pinyin) && $brand_name) {
                $name_pinyin = $this->pinyinRepository->convertMode($brand_name);
                $name_pinyin = $this->pinyinRepository->ucwordsStrtolower($name_pinyin);
            }

            if (empty($brand_first_char)) {
                $brand_first_char = substr($name_pinyin, 0, 1);
            }

            $brand_first_char = strtoupper($brand_first_char);

            /*插入数据*/
            $other = [
                'brand_name' => $brand_name,
                'name_pinyin' => $name_pinyin,
                'brand_letter' => $brand_letter,
                'brand_first_char' => $brand_first_char,
                'site_url' => $site_url,
                'brand_desc' => $brand_desc,
                'brand_logo' => $img_name,
                'index_img' => $index_img,
                'brand_bg' => $brand_bg,
                'is_show' => $is_show,
                'sort_order' => $sort_order,
            ];

            $brand_id = Brand::insertGetId($other);

            if ($brand_id > 0) {
                $is_recommend = isset($_POST['is_recommend']) && !empty($_POST['is_recommend']) ? intval($_POST['is_recommend']) : 0;

                BrandExtend::insert([
                    'brand_id' => $brand_id,
                    'is_recommend' => $is_recommend,
                ]);
            }

            admin_log($_POST['brand_name'], 'add', 'brand');

            /* 清除缓存 */
            if (cache()->has('get_brands_list0')) {
                cache()->forget('get_brands_list0');
            }

            $link[0]['text'] = $GLOBALS['_LANG']['continue_add'];
            $link[0]['href'] = 'brand.php?act=add';

            $link[1]['text'] = $GLOBALS['_LANG']['back_list'];
            $link[1]['href'] = 'brand.php?act=list';

            return sys_msg($GLOBALS['_LANG']['brandadd_succed'], 0, $link);
        }

        /*------------------------------------------------------ */
        //-- 编辑品牌
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'edit') {
            /* 权限判断 */
            admin_priv('brand_manage');

            $brand_id = isset($_REQUEST['id']) && !empty($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;

            $brand = $this->brandManageService->brandInfo($brand_id);

            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['brand_edit']);
            $this->smarty->assign('action_link', ['text' => $GLOBALS['_LANG']['06_goods_brand_list'], 'href' => 'brand.php?act=list&' . list_link_postfix()]);
            $this->smarty->assign('brand', $brand);
            $this->smarty->assign('form_action', 'updata');
            $this->smarty->assign('is_need', 1);

            return $this->smarty->display('brand_info.dwt');
        }

        /*------------------------------------------------------ */
        //-- 更新品牌
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'updata') {
            admin_priv('brand_manage');

            $brand_id = isset($_POST['id']) && !empty($_POST['id']) ? intval($_POST['id']) : 0;
            $brand_name = isset($_POST['brand_name']) && !empty($_POST['brand_name']) ? addslashes(trim($_POST['brand_name'])) : '';
            $brand_letter = isset($_POST['brand_letter']) && !empty($_POST['brand_letter']) ? addslashes(trim($_POST['brand_letter'])) : '';
            $brand_first_char = isset($_POST['brand_first_char']) && !empty($_POST['brand_first_char']) ? addslashes(trim($_POST['brand_first_char'])) : '';
            $old_brandname = isset($_POST['old_brandname']) && !empty($_POST['old_brandname']) ? addslashes(trim($_POST['old_brandname'])) : '';
            $brand_desc = isset($_POST['brand_desc']) && !empty($_POST['brand_desc']) ? addslashes(trim($_POST['brand_desc'])) : '';
            $is_show = isset($_REQUEST['is_show']) && !empty($_REQUEST['is_show']) ? intval($_REQUEST['is_show']) : 0;
            $sort_order = isset($_REQUEST['sort_order']) && !empty($_REQUEST['sort_order']) ? intval($_REQUEST['sort_order']) : 0;
            $site_url = isset($_POST['site_url']) && !empty($_POST['site_url']) ? sanitize_url($_POST['site_url']) : '';
            $name_pinyin = isset($_POST['name_pinyin']) && !empty($_POST['name_pinyin']) ? addslashes($_POST['name_pinyin']) : '';

            if ($brand_name != $old_brandname) {

                /*检查品牌名是否相同*/
                $is_only = Brand::where('brand_name', $brand_name)
                    ->where('brand_id', '<>', $brand_id)
                    ->count();

                if ($is_only > 0) {
                    return sys_msg(sprintf($GLOBALS['_LANG']['brandname_exist'], stripslashes($brand_name)), 1);
                }
            }

            if (empty($name_pinyin) && $brand_name) {
                $name_pinyin = $this->pinyinRepository->convertMode($brand_name);
                $name_pinyin = $this->pinyinRepository->ucwordsStrtolower($name_pinyin);
            }

            if (empty($brand_first_char)) {
                $brand_first_char = substr($name_pinyin, 0, 1);
            }

            $brand_first_char = strtoupper($brand_first_char);

            $other = [
                'brand_name' => $brand_name,
                'name_pinyin' => $name_pinyin,
                'brand_letter' => $brand_letter,
                'brand_first_char' => $brand_first_char,
                'site_url' => $site_url,
                'brand_desc' => $brand_desc,
                'is_show' => $is_show,
                'sort_order' => $sort_order
            ];

            $add_oss_file = [];

            /* 处理图片 */
            if (isset($_FILES['brand_logo'])) {
                $img_name = basename($image->upload_image($_FILES['brand_logo'], 'brandlogo'));
                if (!empty($img_name)) {
                    //有图片上传
                    $other['brand_logo'] = $img_name;
                    $add_oss_file[] = DATA_DIR . '/brandlogo/' . $img_name;
                }
            }

            if (isset($_FILES['index_img'])) {
                $index_img = basename($image->upload_image($_FILES['index_img'], 'indeximg'));
                if (!empty($index_img)) {
                    //有图片上传
                    $other['index_img'] = $index_img;
                    $add_oss_file[] = DATA_DIR . '/indeximg/' . $index_img;
                }
            }

            if (isset($_FILES['brand_bg'])) {
                $brand_bg = basename($image->upload_image($_FILES['brand_bg'], 'brandbg'));
                if (!empty($brand_bg)) {
                    //有图片上传
                    $other['brand_bg'] = $brand_bg;
                    $add_oss_file[] = DATA_DIR . '/brandbg/' . $brand_bg;
                }
            }

            $this->dscRepository->getOssAddFile($add_oss_file);

            Brand::where('brand_id', $brand_id)->update($other);

            if ($brand_id > 0) {
                $is_recommend = isset($_POST['is_recommend']) && !empty($_POST['is_recommend']) ? intval($_POST['is_recommend']) : 0;

                $count = BrandExtend::where('brand_id', $brand_id)->count();

                if ($count > 0) {
                    BrandExtend::where('brand_id', $brand_id)->update([
                        'is_recommend' => $is_recommend
                    ]);
                } else {
                    BrandExtend::insert([
                        'brand_id' => $brand_id,
                        'is_recommend' => $is_recommend
                    ]);
                }
            }

            /* 清除缓存 */
            if (cache()->has('get_brands_list0')) {
                cache()->forget('get_brands_list0');
            }

            admin_log($_POST['brand_name'], 'edit', 'brand');

            $link[0]['text'] = $GLOBALS['_LANG']['back_list'];
            $link[0]['href'] = 'brand.php?act=list&' . list_link_postfix();
            $note = vsprintf($GLOBALS['_LANG']['brandedit_succed'], $_POST['brand_name']);
            return sys_msg($note, 0, $link);
        }

        /*------------------------------------------------------ */
        //-- 商家分类分离平台，独立数据
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'brand_separate') {
            admin_priv('brand_manage');

            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['brand_separate']);

            $this->smarty->assign('page', 1);


            return $this->smarty->display('brand_separate.dwt');
        }

        /*------------------------------------------------------ */
        //-- 商家分类分离平台，独立数据
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'brand_separate_initial') {
            $page = !empty($_REQUEST['page']) ? intval($_REQUEST['page']) : 1;
            $page_size = isset($_REQUEST['page_size']) ? intval($_REQUEST['page_size']) : 1;

            $brand_list = get_seller_brand();
            $brand_list = $this->dsc->page_array($page_size, $page, $brand_list);

            $result['list'] = isset($brand_list['list']) && $brand_list['list'] ? $brand_list['list'][0] : [];

            if ($result['list']) {
                $other = [
                    'brand_id' => $result['list']['brand_id'],
                    'user_brand' => $result['list']['bid']
                ];

                if ($result['list']['user_id']) {

                    /* 更新商家商品品牌ID */
                    Goods::where('user_id', $result['list']['user_id'])
                        ->where('brand_id', $result['list']['bid'])
                        ->where('user_brand', 0)
                        ->update($other);

                    /* 更新会员收藏商家品牌ID */
                    CollectBrand::where('user_id', $result['list']['user_id'])
                        ->where('brand_id', $result['list']['bid'])
                        ->where('user_brand', 0)
                        ->update($other);

                    /* 更新商家优惠活动品牌类型 start */
                    $favourable = FavourableActivity::whereRaw("act_range = 2 AND user_id = '" . $result['list']['user_id'] . "' AND FIND_IN_SET('" . $result['list']['bid'] . "', act_range_ext) AND " . db_create_in($result['list']['bid'], 'user_range_ext', 'NOT'));
                    $favourable = $this->baseRepository->getToArrayGet($favourable);
                    $act_id = $this->baseRepository->getKeyPluck($favourable, 'act_id');

                    if ($act_id) {
                        FavourableActivity::whereIn('act_id', $act_id)
                            ->where('is_user_brand', 0)
                            ->update([
                                'user_range_ext' => DB::raw("user_range_ext")
                            ]);

                        FavourableActivity::whereIn('act_id', $act_id)
                            ->update([
                                'user_range_ext' => DB::raw("REPLACE (act_range_ext, '" . $result['list']['bid'] . "', '" . $result['list']['brand_id'] . "')"),
                                'is_user_brand' => 1
                            ]);
                    }
                    /* 更新商家优惠活动品牌类型 end */

                    $result['status_lang'] = '<span style="color: red;">' . $GLOBALS['_LANG']['update_data_success'] . '</span>';
                } else {
                    $result['status_lang'] = '<span style="color: red;">' . $GLOBALS['_LANG']['update_data_fail'] . '</span>';
                }
            }

            $result['page'] = $brand_list['filter']['page'] + 1;
            $result['page_size'] = $brand_list['filter']['page_size'];
            $result['record_count'] = $brand_list['filter']['record_count'];
            $result['page_count'] = $brand_list['filter']['page_count'];

            $result['is_stop'] = 1;
            if ($page > $brand_list['filter']['page_count']) {
                $result['is_stop'] = 0;

                ShopConfig::where('code', 'brand_belongs')
                    ->update([
                        'value' => 1
                    ]);

                if (cache()->has('shop_config')) {
                    cache()->forget('shop_config');
                }

                $this->config;

                $result['status_lang'] = '<span style="color: red;">' . lang('admin/brand.update_data_success') . '</span>';
            } else {
                $result['filter_page'] = $brand_list['filter']['page'];
            }

            return response()->json($result);
        }

        /*------------------------------------------------------ */
        //-- 生成地区首字母
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'create_brand_letter') {
            admin_priv('brand_manage');

            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['06_goods_brand_list']);

            $record_count = get_brand_list(0, 2);

            $this->smarty->assign('record_count', $record_count);
            $this->smarty->assign('page', 1);

            if ($this->baseRepository->getDiskForeverExists('pin_brands')) {
                $this->baseRepository->getDiskForeverDelete('pin_brands');
            }

            return $this->smarty->display('brand_first_letter.dwt');
        }

        /*------------------------------------------------------ */
        //-- 生成地区首字母
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'create_brand_initial') {

            $page = !empty($_REQUEST['page']) ? intval($_REQUEST['page']) : 1;
            $page_size = isset($_REQUEST['page_size']) ? intval($_REQUEST['page_size']) : 1;

            $brand_list = get_brand_list(0, 1);

            $brand_list = $this->dsc->page_array($page_size, $page, $brand_list);
            $result['list'] = isset($brand_list['list']) && $brand_list['list'] ? $brand_list['list'][0] : [];

            $arr = [];
            if ($result['list']) {

                /* 判断是否有自定义首字母 start */
                if (!empty($result['list']['brand_first_char']) && in_array(strtoupper($result['list']['brand_first_char']), range('A', 'Z'))) {
                    $arr['brand_id'] = $result['list']['brand_id'];
                    $arr['brand_name'] = $result['list']['brand_name'];
                    $arr['brand_letter'] = $result['list']['brand_letter'];
                    $arr['letter'] = strtoupper($result['list']['brand_first_char']);
                } /* 判断是否有自定义首字母 end */
                else {
                    $str_first = strtolower(substr($result['list']['brand_name'], 0, 1));
                    if ($this->dsc->preg_is_letter($str_first)) {
                        $arr['brand_id'] = $result['list']['brand_id'];
                        $arr['brand_name'] = $result['list']['brand_name'];
                        $arr['brand_letter'] = $result['list']['brand_letter'];
                        $arr['letter'] = strtoupper($str_first);
                    } else {
                        $pin = new Pinyin();
                        $letters = range('A', 'Z');
                        foreach ($letters as $key => $val) {
                            $str = strtoupper($result['list']['brand_name']);
                            $str = substr($str, 0, 1);

                            if (in_array($str, range('A', 'Z'))) {
                                $arr['brand_id'] = $result['list']['brand_id'];
                                $arr['brand_name'] = $result['list']['brand_name'];
                                $arr['brand_letter'] = $result['list']['brand_letter'];
                                $arr['letter'] = $str;
                            } else {
                                if (strtolower(substr($result['list']['brand_name'], 0, 1)) == strtolower($val)) {
                                    $arr['brand_id'] = $result['list']['brand_id'];
                                    $arr['brand_name'] = $result['list']['brand_name'];
                                    $arr['brand_letter'] = $result['list']['brand_letter'];
                                    $arr['letter'] = $val;
                                } else {
                                    if (strtolower($val) == substr($pin->Pinyin($result['list']['brand_name'], EC_CHARSET), 0, 1)) {
                                        $arr['brand_id'] = $result['list']['brand_id'];
                                        $arr['brand_name'] = $result['list']['brand_name'];
                                        $arr['brand_letter'] = $result['list']['brand_letter'];
                                        $arr['letter'] = $val;
                                    }
                                }
                            }
                        }
                    }

                    if (isset($arr['letter']) && !empty($arr['letter'])) {
                        Brand::where('brand_id', $result['list']['brand_id'])->update(['brand_first_char' => $arr['letter']]);
                    }
                }

                $result['list'] = $arr;

                if ($result['list']) {
                    $pin_brands = $this->baseRepository->getDiskForeverData('pin_brands');
                    if ($pin_brands === false) {
                        $this->baseRepository->setDiskForever('pin_brands', [$result['list']]);
                    } else {
                        array_push($pin_brands, $result['list']);
                        $this->baseRepository->setDiskForever('pin_brands', $pin_brands);
                    }
                }
            }

            $result['page'] = $brand_list['filter']['page'] + 1;
            $result['page_size'] = $brand_list['filter']['page_size'];
            $result['record_count'] = $brand_list['filter']['record_count'];
            $result['page_count'] = $brand_list['filter']['page_count'];

            $result['is_stop'] = 1;
            if ($page > $brand_list['filter']['page_count']) {
                $result['is_stop'] = 0;
            } else {
                $result['filter_page'] = $brand_list['filter']['page'];
            }

            return response()->json($result);
        }

        /*------------------------------------------------------ */
        //-- 编辑品牌中文名称
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'edit_brand_name') {
            $check_auth = check_authz_json('brand_manage');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $id = intval($_POST['id']);
            $name = json_str_iconv(trim($_POST['val']));

            $is_only = Brand::where('brand_name', $name)
                ->where('brand_id', '<>', $id)
                ->count();

            /* 检查名称是否重复 */
            if ($is_only > 0) {
                return make_json_error(sprintf($GLOBALS['_LANG']['brandname_exist'], $name));
            } else {
                $res = Brand::where('brand_id', $id)
                    ->update([
                        'brand_name' => $name
                    ]);

                if ($res) {
                    admin_log($name, 'edit', 'brand');
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

            $is_only = Brand::where('brand_letter', $name)
                ->where('brand_id', '<>', $id)
                ->count();

            /* 检查名称是否重复 */
            if ($is_only > 0) {
                return make_json_error(sprintf($GLOBALS['_LANG']['brandname_exist'], $name));
            } else {
                $res = Brand::where('brand_id', $id)
                    ->update([
                        'brand_letter' => $name
                    ]);

                if ($res) {
                    admin_log($name, 'edit', 'brand');
                    return make_json_result(stripslashes($name));
                } else {
                    return make_json_result(sprintf($GLOBALS['_LANG']['brandedit_fail'], $name));
                }
            }
        } elseif ($_REQUEST['act'] == 'add_brand') {
            $brand_name = empty($_REQUEST['brand']) ? '' : json_str_iconv(trim($_REQUEST['brand']));

            $is_only = Brand::where('brand_name', $brand_name)
                ->count();

            if ($is_only > 0) {
                return make_json_error($GLOBALS['_LANG']['brand_name_exist']);
            } else {
                $brand_id = Brand::insertGetId([
                    'brand_name' => $brand_name
                ]);

                $arr = ["id" => $brand_id, "brand" => $brand_name];

                return make_json_result($arr);
            }
        }

        /*------------------------------------------------------ */
        //-- 编辑排序序号
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'edit_sort_order') {
            $check_auth = check_authz_json('brand_manage');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $id = intval($_POST['id']);
            $order = intval($_POST['val']);

            $res = Brand::where('brand_id', $id)
                ->update([
                    'sort_order' => $order
                ]);

            if ($res) {
                $brand_name = Brand::where('brand_id', $id)->value('brand_name');
                admin_log(addslashes($brand_name), 'edit', 'brand');
            }

            return make_json_result($order);
        }

        /*------------------------------------------------------ */
        //-- 切换是否显示
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'toggle_show') {
            $check_auth = check_authz_json('brand_manage');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $id = intval($_POST['id']);
            $val = intval($_POST['val']);

            Brand::where('brand_id', $id)
                ->update([
                    'is_show' => $val
                ]);

            return make_json_result($val);
        }

        /*------------------------------------------------------ */
        //-- 切换是否显示
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'toggle_recommend') {
            $check_auth = check_authz_json('brand_manage');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $brand_id = intval($_POST['id']);
            $is_recommend = intval($_POST['val']);

            $count = BrandExtend::where('brand_id', $brand_id)->count();

            if ($count > 0) {
                BrandExtend::where('brand_id', $brand_id)
                    ->update([
                        'is_recommend' => $is_recommend
                    ]);
            } else {
                BrandExtend::insert([
                    'brand_id' => $brand_id,
                    'is_recommend' => $is_recommend
                ]);
            }

            return make_json_result($is_recommend);
        }

        /*------------------------------------------------------ */
        //-- 删除品牌
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'remove') {
            $check_auth = check_authz_json('brand_manage');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $id = intval($_GET['id']);

            $this->dscRepository->getDelBatch('', $id, ['brand_logo'], 'brand_id', Brand::whereRaw(1), 0, DATA_DIR . '/brandlogo/'); //删除图片

            Brand::where('brand_id', $id)->delete();
            BrandExtend::where('brand_id', $id)->delete();

            /* 更新商品的品牌编号 */
            Goods::where('brand_id', $id)->update([
                'brand_id' => 0
            ]);

            /* 清除缓存 */
            if (cache()->has('get_brands_list0')) {
                cache()->forget('get_brands_list0');
            }

            /* 清理缓存文件 */
            if ($this->baseRepository->getDiskForeverExists('pin_brands')) {
                $this->baseRepository->getDiskForeverDelete('pin_brands');
            }

            $url = 'brand.php?act=query&' . str_replace('act=remove', '', request()->server('QUERY_STRING'));
            return dsc_header("Location: $url\n");
        }

        /*------------------------------------------------------ */
        //-- 删除品牌图片
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'drop_logo') {
            /* 权限判断 */
            admin_priv('brand_manage');
            $brand_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

            $this->dscRepository->getDelBatch('', $brand_id, ['brand_logo'], 'brand_id', Brand::whereRaw(1), 0, DATA_DIR . '/brandlogo/'); //删除图片

            Brand::where('brand_id', $brand_id)->update([
                'brand_logo' => ''
            ]);

            $link = [['text' => $GLOBALS['_LANG']['brand_edit_lnk'], 'href' => 'brand.php?act=edit&id=' . $brand_id], ['text' => $GLOBALS['_LANG']['brand_list_lnk'], 'href' => 'brand.php?act=list']];
            return sys_msg($GLOBALS['_LANG']['drop_brand_logo_success'], 0, $link);
        }
    }
}
