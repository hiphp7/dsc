<?php

namespace App\Modules\Admin\Controllers;

use App\Libraries\Image;
use App\Models\Brand;
use App\Models\Goods;
use App\Models\GoodsGallery;
use App\Models\GoodsLib;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\DscRepository;
use App\Services\Goods\GoodsManageService;

/**
 * 商品库商品批量上传、修改
 */
class GoodsLibBatchController extends InitController
{
    protected $dscRepository;
    protected $goodsManageService;
    protected $baseRepository;

    public function __construct(
        DscRepository $dscRepository,
        GoodsManageService $goodsManageService,
        BaseRepository $baseRepository
    )
    {
        $this->dscRepository = $dscRepository;
        $this->goodsManageService = $goodsManageService;
        $this->baseRepository = $baseRepository;
    }

    public function index()
    {
        load_helper('goods', 'admin');
        load_helper('goods', 'admin');

        /*------------------------------------------------------ */
        //-- 批量上传
        /*------------------------------------------------------ */

        if ($_REQUEST['act'] == 'add') {
            /* 检查权限 */
            admin_priv('goods_lib_list');

            $lang_list = [
                'UTF8' => $GLOBALS['_LANG']['charset']['utf8'],
                'GB2312' => $GLOBALS['_LANG']['charset']['zh_cn'],
                'BIG5' => $GLOBALS['_LANG']['charset']['zh_tw'],
            ];

            /* 取得可选语言 */
            $download_list = $this->dscRepository->getDdownloadTemplate(resource_path('lang'));

            $data_format_array = [
                'dscmall' => $GLOBALS['_LANG']['export_dscmall'],
                'taobao' => $GLOBALS['_LANG']['export_taobao'],
            ];
            $this->smarty->assign('data_format', $data_format_array);
            $this->smarty->assign('lang_list', $lang_list);
            $this->smarty->assign('download_list', $download_list);
            $goods_id = 0;
            set_default_filter($goods_id, 0, 0, 0, 'goods_lib_cat'); //设置默认筛选

            /* 参数赋值 */
            $ur_here = $GLOBALS['_LANG']['goods_lib_batch_add'];
            $this->smarty->assign('ur_here', $ur_here);
            $this->smarty->assign('action_link', ['href' => 'goods_lib.php?act=list', 'text' => $GLOBALS['_LANG']['01_goods_list']]);

            /* 显示模板 */

            return $this->smarty->display('goods_lib_batch_add.dwt');
        }

        /*------------------------------------------------------ */
        //-- 批量上传：处理
        /*------------------------------------------------------ */

        elseif ($_REQUEST['act'] == 'upload') {
            /* 检查权限 */
            admin_priv('goods_lib_list');

            /* 将文件按行读入数组，逐行进行解析 */
            $line_number = 0;
            $arr = [];
            $goods_list = [];
            $field_list = array_keys($GLOBALS['_LANG']['upload_goods_lib']); // 字段列表
            $data = file($_FILES['file']['tmp_name']);
            if ($_POST['data_cat'] == 'dscmall') {
                foreach ($data as $line) {
                    // 跳过第一行
                    if ($line_number == 0) {
                        $line_number++;
                        continue;
                    }

                    // 转换编码
                    if (($_POST['charset'] != 'UTF8') && (strpos(strtolower(EC_CHARSET), 'utf') === 0)) {
                        $line = dsc_iconv($_POST['charset'], 'UTF8', $line);
                    }

                    // 初始化
                    $arr = [];
                    $buff = '';
                    $quote = 0;
                    $len = strlen($line);


                    for ($i = 0; $i < $len; $i++) {
                        $char = $line[$i];


                        if ('\\' == $char) {
                            $i++;
                            $char = $line[$i];

                            switch ($char) {
                                case '"':
                                    $buff .= '"';
                                    break;
                                case '\'':
                                    $buff .= '\'';
                                    break;
                                case ',':
                                    $buff .= ',';
                                    break;
                                default:
                                    $buff .= '\\' . $char;
                                    break;
                            }
                        } elseif ('"' == $char) {
                            if (0 == $quote) {
                                $quote++;
                            } else {
                                $quote = 0;
                            }
                        } elseif (',' == $char) {
                            if (0 == $quote) {
                                if (!isset($field_list[count($arr)])) {
                                    continue;
                                }
                                $field_name = $field_list[count($arr)];
                                $arr[$field_name] = trim($buff);
                                $buff = '';
                                $quote = 0;
                            } else {
                                $buff .= $char;
                            }
                        } else {
                            $buff .= $char;
                        }

                        if ($i == $len - 1) {
                            if (!isset($field_list[count($arr)])) {
                                continue;
                            }
                            $field_name = $field_list[count($arr)];
                            $arr[$field_name] = trim($buff);
                        }
                    }
                    $goods_list[] = $arr;

                }
            } elseif ($_POST['data_cat'] == 'taobao') {
                $id_is = 0;
                foreach ($data as $line) {
                    // 跳过第一行
                    if ($line_number == 0) {
                        $line_number++;
                        continue;
                    }

                    // 初始化
                    $arr = [];
                    $line_list = explode("\t", $line);
                    $arr['goods_name'] = trim($line_list[0], '"');

                    $max_id = Goods::max('goods_id');
                    $max_id = $max_id ? $max_id + $id_is : 0;
                    $id_is++;
                    $goods_sn = $this->goodsManageService->generateGoodSn($max_id);
                    $arr['goods_sn'] = $goods_sn;
                    $arr['brand_name'] = '';
                    $arr['market_price'] = $line_list[7];
                    $arr['shop_price'] = $line_list[7];
                    $arr['original_img'] = $line_list[25];
                    $arr['keywords'] = '';
                    $arr['goods_brief'] = '';
                    $arr['goods_desc'] = strip_tags($line_list[24]);
                    $arr['goods_desc'] = substr($arr['goods_desc'], 1, -1);
                    $arr['is_on_sale'] = 1;
                    $arr['is_alone_sale'] = 0;
                    $arr['is_real'] = 1;

                    $goods_list[] = $arr;
                }
            }
            session(['goods_list' => $goods_list]);

            $this->smarty->assign('goods_class', $GLOBALS['_LANG']['g_class']);
            //$this->smarty->assign('goods_list', $goods_list);
            $this->smarty->assign('page', 1);


            // 字段名称列表
            $this->smarty->assign('title_list', $GLOBALS['_LANG']['upload_goods_lib']);

            // 显示的字段列表
            $this->smarty->assign('field_show', ['goods_name' => true, 'goods_sn' => true, 'brand_name' => true, 'market_price' => true, 'shop_price' => true]);

            /* 参数赋值 */
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['goods_upload_confirm']);

            /* 显示模板 */

            return $this->smarty->display('goods_lib_batch_confirm.dwt');
        } /*异步处理上传*/
        elseif ($_REQUEST['act'] == 'creat') {
            $result = ['list' => [], 'is_stop' => 0];
            $page = !empty($_REQUEST['page']) ? intval($_REQUEST['page']) : 0;
            $page_size = isset($_REQUEST['page_size']) ? intval($_REQUEST['page_size']) : 1;


            if (session()->has('goods_list')) {
                $goods_list = session('goods_list');
            } else {
                $goods_list = [];
            }

            if ($goods_list) {
                $goods_list = $this->dsc->page_array($page_size, $page, $goods_list);
                $result['list'] = isset($goods_list['list']) && $goods_list['list'] ? $goods_list['list'][0] : [];
                $result['page'] = $goods_list['filter']['page'] + 1;
                $result['page_size'] = $goods_list['filter']['page_size'];
                $result['index'] = $result['page'] - $result['page_size'] - 1;
                $result['record_count'] = $goods_list['filter']['record_count'];
                $result['page_count'] = $goods_list['filter']['page_count'];

                $result['is_stop'] = 1;
                if ($page > $goods_list['filter']['page_count']) {
                    $result['is_stop'] = 0;
                }
            }

            return response()->json($result);
        }

        /*------------------------------------------------------ */
        //-- 批量上传：�        �库
        /*------------------------------------------------------ */

        elseif ($_REQUEST['act'] == 'insert') {
            /* 检查权限 */
            admin_priv('goods_lib_list');

            if (isset($_POST['checked'])) {
                $image = new Image([$GLOBALS['_CFG']['bgcolor']]);

                /* 字段默认值 */
                $default_value = [
                    'brand_id' => 0,
                    'goods_weight' => 0,
                    'market_price' => 0,
                    'shop_price' => 0,
                    'is_real' => 1,
                    'is_on_sale' => 1,
                    'goods_type' => 0,
                ];

                /* 查询品牌列表 */
                $brand_list = [];
                $res = Brand::select('brand_id', 'brand_name');
                $res = $this->baseRepository->getToArrayGet($res);
                foreach ($res as $row) {
                    $brand_list[$row['brand_name']] = $row['brand_id'];
                }

                /* 字段列表 */
                $field_list = array_keys($GLOBALS['_LANG']['upload_goods_lib']);
                $field_list[] = 'goods_class'; //实体或虚拟商品

                /* 获取商品good id */
                $max_id = Goods::max('goods_id');
                $max_id = $max_id ? $max_id + 1 : 1;
                /* 循环插入商品数据 */

                foreach ($_POST['checked'] as $key => $value) {
                    // 合并
                    $field_arr = [
                        'cat_id' => $_POST['cat'],
                        'add_time' => gmtime(),
                        'last_update' => gmtime(),
                    ];
                    foreach ($field_list as $field) {
                        // 转换编码
                        $field_value = isset($_POST[$field][$value]) ? $_POST[$field][$value] : '';

                        /* 虚拟商品处理 */
                        if ($field == 'goods_class') {
                            $field_value = intval($field_value);
                            if ($field_value == G_CARD) {
                                $field_arr['extension_code'] = 'virtual_card';
                            }
                            continue;
                        }

                        // 如果字段值为空，且有默认值，取默认值
                        $field_arr[$field] = !isset($field_value) && isset($default_value[$field]) ? $default_value[$field] : $field_value;

                        // 特殊处理
                        if (!empty($field_value)) {

                            // 图片路径
                            if (in_array($field, ['original_img', 'goods_img', 'goods_thumb'])) {
                                if (strpos($field_value, '|;') > 0) {
                                    $field_value = explode(':', $field_value);
                                    $field_value = $field_value['0'];
                                    @copy(storage_public('images/' . $field_value . '.tbi'), storage_public('images/' . $field_value . '.jpg'));
                                    if (is_file(storage_public('images/' . $field_value . '.jpg'))) {
                                        $field_arr[$field] = IMAGE_DIR . '/' . $field_value . '.jpg';
                                    }
                                } else {
                                    $field_arr[$field] = IMAGE_DIR . '/' . $field_value;
                                }
                            } // 品牌
                            elseif ($field == 'brand_name') {
                                if (isset($brand_list[$field_value])) {
                                    $field_arr['brand_id'] = $brand_list[$field_value];
                                } else {
                                    $data = [
                                        'brand_name' => addslashes($field_value)
                                    ];
                                    $brand_id = Brand::insertGetId($data);

                                    $brand_list[$field_value] = $brand_id;
                                    $field_arr['brand_id'] = $brand_id;
                                }
                            } // 数值型
                            elseif (in_array($field, ['goods_weight', 'market_price', 'shop_price'])) {
                                $field_arr[$field] = floatval($field_value);
                            } // bool型
                            elseif (in_array($field, ['is_on_sale', 'is_real'])) {
                                $field_arr[$field] = intval($field_value) > 0 ? 1 : 0;
                            }
                        }

                        if ($field == 'is_real') {
                            $field_arr[$field] = intval($_POST['goods_class'][$key]);
                        }
                    }

                    if (empty($field_arr['goods_sn'])) {
                        $field_arr['goods_sn'] = $this->goodsManageService->generateGoodSn($max_id);
                    }

                    if ($field_arr && $field_arr['goods_name']) {

                        $field_arrs = [
                            'cat_id' => $field_arr['cat_id'],
                            'add_time' => $field_arr['add_time'],
                            'last_update' => $field_arr['last_update'],
                            'goods_name' => $field_arr['goods_name'],
                            'goods_sn' => $field_arr['goods_sn'],
                            'brand_id' => $field_arr['brand_id'] ?? 0,
                            'market_price' => $field_arr['market_price'],
                            'shop_price' => $field_arr['shop_price'],
                            'original_img' => $field_arr['original_img'],
                            'goods_img' => $field_arr['goods_img'],
                            'goods_thumb' => $field_arr['goods_thumb'],
                            'keywords' => $field_arr['keywords'],
                            'goods_brief' => $field_arr['goods_brief'],
                            'goods_desc' => $field_arr['goods_desc'],
                            'goods_weight' => $field_arr['goods_weight'],
                            'is_on_sale' => $field_arr['is_on_sale'],
                            'is_real' => $field_arr['is_real'],
                            'extension_code' => $field_arr['extension_code'] ?? 0,
                        ];
                        $goods_lib_id = GoodsLib::insertGetId($field_arrs);

                        $max_id = $goods_lib_id + 1;

                        /* 如果图片不为空,修改商品图片，插入商品相册*/
                        if (!empty($field_arr['original_img']) || !empty($field_arr['goods_img']) || !empty($field_arr['goods_thumb'])) {
                            $goods_img = '';
                            $goods_thumb = '';
                            $original_img = '';
                            $goods_gallery = [];
                            $goods_gallery['goods_id'] = $goods_lib_id;

                            if (!empty($field_arr['original_img'])) {
                                //设置商品相册原图和商品相册图
                                if ($GLOBALS['_CFG']['auto_generate_gallery']) {
                                    $ext = substr($field_arr['original_img'], strrpos($field_arr['original_img'], '.'));
                                    $img = dirname($field_arr['original_img']) . '/' . $image->random_filename() . $ext;
                                    $gallery_img = dirname($field_arr['original_img']) . '/' . $image->random_filename() . $ext;
                                    @copy(storage_public($field_arr['original_img']), storage_public($img));
                                    @copy(storage_public($field_arr['original_img']), storage_public($gallery_img));
                                    $goods_gallery['img_original'] = $this->goodsManageService->reformatImageName('gallery', $goods_gallery['goods_id'], $img, 'source');
                                }
                                //设置商品原图
                                if ($GLOBALS['_CFG']['retain_original_img']) {
                                    $original_img = $this->goodsManageService->reformatImageName('goods', $goods_gallery['goods_id'], $field_arr['original_img'], 'source');
                                } else {
                                    @unlink(storage_public($field_arr['original_img']));
                                }
                            }

                            if (!empty($field_arr['goods_img'])) {
                                //设置商品相册图
                                if ($GLOBALS['_CFG']['auto_generate_gallery'] && !empty($gallery_img)) {
                                    $goods_gallery['img_url'] = $this->goodsManageService->reformatImageName('gallery', $goods_gallery['goods_id'], $gallery_img, 'goods');
                                }
                                //设置商品图
                                $goods_img = $this->goodsManageService->reformatImageName('goods', $goods_gallery['goods_id'], $field_arr['goods_img'], 'goods');
                            }

                            if (!empty($field_arr['goods_thumb'])) {
                                //设置商品相册缩略图
                                if ($GLOBALS['_CFG']['auto_generate_gallery']) {
                                    $ext = substr($field_arr['goods_thumb'], strrpos($field_arr['goods_thumb'], '.'));
                                    $gallery_thumb = dirname($field_arr['goods_thumb']) . '/' . $image->random_filename() . $ext;
                                    @copy(storage_public($field_arr['goods_thumb']), storage_public($gallery_thumb));
                                    $goods_gallery['thumb_url'] = $this->goodsManageService->reformatImageName('gallery_thumb', $goods_gallery['goods_id'], $gallery_thumb, 'thumb');
                                }
                                //设置商品缩略图
                                $goods_thumb = $this->goodsManageService->reformatImageName('goods_thumb', $goods_gallery['goods_id'], $field_arr['goods_thumb'], 'thumb');
                            }

                            //修改商品图
                            $data = [
                                'goods_img' => $goods_img,
                                'goods_thumb' => $goods_thumb,
                                'original_img' => $original_img
                            ];
                            Goods::where('goods_id', $goods_gallery['goods_id'])->update($data);
                            //添加商品相册图
                            if ($GLOBALS['_CFG']['auto_generate_gallery']) {
                                GoodsGallery::insert($goods_gallery);
                            }
                        }
                    }
                }
            }

            // 记录日志
            admin_log('', 'batch_upload', 'goods');

            /* 显示提示信息，返回商品列表 */
            $link[] = ['href' => 'goods_lib.php?act=list', 'text' => $GLOBALS['_LANG']['01_goods_list']];
            return sys_msg($GLOBALS['_LANG']['batch_upload_ok'], 0, $link);
        }


        /*------------------------------------------------------ */
        //-- 下载文件
        /*------------------------------------------------------ */

        elseif ($_REQUEST['act'] == 'download') {
            /* 检查权限 */
            admin_priv('goods_lib_list');

            // 文件标签
            // Header("Content-type: application/octet-stream");
            header("Content-type: application/vnd.ms-excel; charset=utf-8");
            Header("Content-Disposition: attachment; filename=goods_list.csv");

            // 下载
            if ($_GET['charset'] != $GLOBALS['_CFG']['lang']) {
                $lang_file = '../languages/' . $_GET['charset'] . '/admin/goods_batch.php';
                if (file_exists($lang_file)) {
                    unset($GLOBALS['_LANG']['upload_goods_lib']);
                    require($lang_file);
                }
            }

            if (isset($GLOBALS['_LANG']['upload_goods_lib'])) {
                /* 创建字符集转换对象 */
                if ($_GET['charset'] == 'zh-CN' || $_GET['charset'] == 'zh-TW') {
                    $to_charset = $_GET['charset'] == 'zh-CN' ? 'GB2312' : 'BIG5';
                    echo dsc_iconv(EC_CHARSET, $to_charset, join(',', $GLOBALS['_LANG']['upload_goods_lib']));
                } else {
                    echo join(',', $GLOBALS['_LANG']['upload_goods_lib']);
                }
            } else {
                echo 'error: ' . $GLOBALS['_LANG']['upload_goods_lib'] . ' not exists';
            }
        }

        /*------------------------------------------------------ */
        //-- 取得商品
        /*------------------------------------------------------ */

        elseif ($_REQUEST['act'] == 'get_goods') {
            $filter = app(\StdClass::class);

            $filter->cat_id = intval($_GET['cat_id']);
            $filter->brand_id = intval($_GET['brand_id']);
            $filter->real_goods = -1;
            $arr = get_goods_list($filter);

            return make_json_result($arr);
        }
    }
}
