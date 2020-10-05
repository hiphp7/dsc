<?php

use App\Models\Article;
use App\Models\Brand;
use App\Models\GalleryAlbum;
use App\Models\Goods;
use App\Models\HomeTemplates;
use App\Models\PicAlbum;
use App\Models\Seckill;
use App\Models\SellerTemplateApply;
use App\Models\ShopConfig;
use App\Models\TemplateMall;
use App\Models\TemplatesLeft;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\DscRepository;
use App\Services\Article\ArticleCatService;
use App\Services\Category\CategoryService;
use App\Services\Merchant\MerchantCommonService;
use Illuminate\Support\Facades\Storage;

/**
 * 生成缓存文件
 *
 * @access  public
 * @param string $out 缓存文件内容
 * @param sting $cache_id 商家id
 *
 * @return  sring
 */
function create_html($out = '', $cache_id = 0, $cachename = '', $suffix = '', $topic_type = 0)
{
    /* 格式化smarty */
    $GLOBALS['smarty']->cache_lifetime = $GLOBALS['_CFG']['cache_time'];
    $seller_tem = '';
    if ($topic_type == 1) {
        $GLOBALS['smarty']->cache_dir = storage_public(DATA_DIR . '/topic');
        $seller_tem = "topic_" . $cache_id;
    } elseif ($topic_type == 2) {
        $GLOBALS['smarty']->cache_dir = storage_public(DATA_DIR);
    } elseif ($topic_type == 3) {
        $GLOBALS['smarty']->cache_dir = storage_public(DATA_DIR . '/home_templates');
        $seller_tem = $GLOBALS['_CFG']['template'];
    } elseif ($topic_type == 5) {
        $GLOBALS['smarty']->cache_dir = storage_public(DATA_DIR . '/cms_templates');
        $seller_tem = $GLOBALS['_CFG']['template'];
    } else {
        $GLOBALS['smarty']->cache_dir = storage_public(DATA_DIR . '/seller_templates');
        if ($cache_id > 0) {
            $seller_tem = "seller_tem_" . $cache_id;
        } else {
            $seller_tem = "seller_tem";
        }
    }
    if ($topic_type != 2) {
        $suffix = $suffix . "/temp";
    }
    $back = '';
    if ($out) {
        $out = str_replace("\r", '', $out);
        while (strpos($out, "\n\n") !== false) {
            $out = str_replace("\n\n", "\n", $out);
        }
        $hash_dir = $GLOBALS['smarty']->cache_dir . '/' . $seller_tem . "/" . $suffix;
        if (!is_dir($hash_dir)) {
            make_dir($hash_dir);
        }

        if ($cachename) {
            $files = explode(".", $cachename);
            $files_count = count($files) - (count($files) - 1);
            $suffix_name = $files[$files_count];

            if (count($files) > 2) {
                $path = count($files) - 1;

                $name = '';
                if ($files[$path]) {
                    foreach ($files[$path] as $row) {
                        $name .= $row . ".";
                    }

                    $name = substr($name, 0, -1);
                }

                $file_path = explode("/", $name);
                if ($file_path > 2) {
                    $path = count($file_path) - 1;
                    $cachename = $file_path[$path];
                } else {
                    $cachename = $file_path[0];
                }
            } else {
                $cachename = $files[0];
            }

            $file_put = write_static_file_cache($cachename, $out, $suffix_name, $hash_dir . '/', 1);
        } else {
            $file_put = false;
        }

        if ($file_put === false) {
            trigger_error('can\'t write:' . $hash_dir . '/' . $cachename);
            $back = '';
        } else {
            $back = $cachename;
        }

        $GLOBALS['smarty']->template = [];
    } else {
        $back = '';
    }


    return $back; // 返回html数据
}

/**
 * 读取文件内容
 *
 * @access  public
 * @param string $name 路径
 *
 * @return  sring
 */
function get_html_file($name)
{
    if (file_exists($name)) {
        $GLOBALS['smarty']->_current_file = $name;
        $name = read_static_flie_cache($name, '', '', 1);
        $source = $GLOBALS['smarty']->fetch_str($name);
    } else {
        $source = '';
    }

    return $source;
}

/**
 * 读取缓存文件
 *
 * @access  public
 * @param intval $ru_id 路径
 * @param intval $type 表示  0 后台编辑模板 1前台预览模板
 *
 * @return  sring
 */
function get_seller_templates($ru_id = 0, $type = 0, $tem = '', $pre_type = 0)
{
    if ($type == 0) {
        $seller_templates = 'pc_page';
    } elseif ($type == 2) {
        $seller_templates = 'pc_head';
    } else {
        $seller_templates = 'pc_html';
    }

    $arr['tem'] = $tem;
    $arr['is_temp'] = 0;
    $seller_tem = 'seller_tem_' . $ru_id;
    if ($ru_id == 0) {
        $seller_tem = 'seller_tem';
    }
    $filename = storage_public(DATA_DIR . '/seller_templates' . '/' . $seller_tem . "/" . $arr['tem'] . "/" . $seller_templates . '.php');
    if ($pre_type == 1) {
        $pre_file = storage_public(DATA_DIR . '/seller_templates' . '/' . $seller_tem . "/" . $arr['tem'] . "/temp");
        if (is_dir($pre_file)) {
            $filename = $pre_file . "/" . $seller_templates . '.php';
            $arr['is_temp'] = 1;
        }
    }
    $arr['out'] = get_html_file($filename);
    return $arr;
}

/**
 * 获得商家店铺模版的信息
 *
 * @access  private
 * @param string $template_name 模版名
 * @param string $ru_id 商家id
 * @return  array
 */
function get_seller_template_info($template_name = '', $ru_id = 0, $theme = '')
{
    if ($ru_id > 0) {
        $seller_tem = "seller_tem_" . $ru_id;
    } else {
        $seller_tem = 'seller_tem';
    }

    $info = [];
    $ext = ['png', 'gif', 'jpg', 'jpeg'];

    $info['code'] = $template_name;
    $info['screenshot'] = '';

    $disk = 'public';
    if (isset($GLOBALS['_CFG']['open_oss']) && $GLOBALS['_CFG']['open_oss'] == 1) {
        if (!isset($GLOBALS['_CFG']['cloud_storage'])) {
            $cloud_storage = ShopConfig::where('code', 'cloud_storage')->value('value');
            $cloud_storage = $cloud_storage ? $cloud_storage : 0;
        } else {
            $cloud_storage = $GLOBALS['_CFG']['cloud_storage'];
        }

        if ($cloud_storage == 1) {
            $disk = 'obs';
        } else {
            $disk = 'oss';
        }
    }

    if ($theme == '') {
        foreach ($ext as $val) {

            $screenshot_exists = Storage::disk($disk)->has(DATA_DIR . '/seller_templates/' . $seller_tem . '/' . $template_name . '/screenshot.' . $val);
            if ($screenshot_exists) {
                $info['screenshot'] = Storage::disk($disk)->url(DATA_DIR . '/seller_templates/' . $seller_tem . '/' . $template_name . '/screenshot.' . $val);
            }

            $template_exists = Storage::disk($disk)->has(DATA_DIR . '/seller_templates/' . $seller_tem . '/' . $template_name . '/template.' . $val);
            if ($template_exists) {
                $info['template'] = Storage::disk($disk)->url(DATA_DIR . '/seller_templates/' . $seller_tem . '/' . $template_name . '/template.' . $val);
            }
        }

        $info_path = storage_public(DATA_DIR . '/seller_templates/' . $seller_tem . '/' . $template_name . '/tpl_info.txt');
    } else {
        foreach ($ext as $val) {

            $screenshot_exists = Storage::disk($disk)->has(DATA_DIR . '/home_templates/' . $theme . '/' . $template_name . '/screenshot.' . $val);
            if ($screenshot_exists) {
                $info['screenshot'] = Storage::disk($disk)->url(DATA_DIR . '/home_templates/' . $theme . '/' . $template_name . '/screenshot.' . $val);
            }

            $template_exists = Storage::disk($disk)->has(DATA_DIR . '/home_templates/' . $theme . '/' . $template_name . '/template.' . $val);
            if ($template_exists) {
                $info['template'] = Storage::disk($disk)->url(DATA_DIR . '/home_templates/' . $theme . '/' . $template_name . '/template.' . $val);
            }
        }

        $info_path = storage_public(DATA_DIR . '/home_templates/' . $theme . '/' . $template_name . '/tpl_info.txt');
    }

    if (file_exists($info_path) && !empty($template_name)) {
        $arr = @array_slice(file($info_path), 0, 9);

        $arr[1] = isset($arr[1]) ? $arr[1] : '';
        $arr[2] = isset($arr[2]) ? $arr[2] : '';
        $arr[3] = isset($arr[3]) ? $arr[3] : '';
        $arr[4] = isset($arr[4]) ? $arr[4] : '';
        $arr[5] = isset($arr[5]) ? $arr[5] : '';
        $arr[6] = isset($arr[6]) ? $arr[6] : '';
        $arr[7] = isset($arr[7]) ? $arr[7] : '';
        $arr[8] = isset($arr[8]) ? $arr[8] : '';

        $arr[1] = addslashes(iconv("GB2312", "UTF-8", $arr[1]));
        $arr[2] = addslashes(iconv("GB2312", "UTF-8", $arr[2]));
        $arr[3] = addslashes(iconv("GB2312", "UTF-8", $arr[3]));
        $arr[4] = addslashes(iconv("GB2312", "UTF-8", $arr[4]));
        $arr[5] = addslashes(iconv("GB2312", "UTF-8", $arr[5]));
        $arr[6] = addslashes(iconv("GB2312", "UTF-8", $arr[6]));
        $arr[7] = addslashes(iconv("GB2312", "UTF-8", $arr[7]));
        $arr[8] = addslashes(iconv("GB2312", "UTF-8", $arr[8]));

        $template_name = explode('：', $arr[1]);
        $template_uri = explode('：', $arr[2]);
        $template_desc = explode('：', $arr[3]);
        $template_version = explode('：', $arr[4]);
        $template_author = explode('：', $arr[5]);
        $author_uri = explode('：', $arr[6]);
        $tpl_dwt_code = explode('：', $arr[7]);
        $win_goods_type = explode('：', $arr[8]);

        $info['name'] = isset($template_name[1]) ? trim($template_name[1]) : '';
        $info['uri'] = isset($template_uri[1]) ? trim($template_uri[1]) : '';
        $info['desc'] = isset($template_desc[1]) ? trim($template_desc[1]) : '';
        $info['version'] = isset($template_version[1]) ? trim($template_version[1]) : '';
        $info['author'] = isset($template_author[1]) ? trim($template_author[1]) : '';
        $info['author_uri'] = isset($author_uri[1]) ? trim($author_uri[1]) : '';
        $info['dwt_code'] = isset($tpl_dwt_code[1]) ? trim($tpl_dwt_code[1]) : '';
        $info['win_goods_type'] = isset($win_goods_type[1]) ? trim($win_goods_type[1]) : '';
        $info['sort'] = substr($info['code'], -1, 1);
    } else {
        $info['name'] = '';
        $info['uri'] = '';
        $info['desc'] = '';
        $info['version'] = '';
        $info['author'] = '';
        $info['author_uri'] = '';
        $info['dwt_code'] = '';
        $info['sort'] = '';
    }

    return $info;
}

/*获取页面左侧的属性
 * $type 0：头部  1：中间
 *  $tem模板名称
 *  */
function getleft_attr($type = 0, $ru_id = 0, $tem = '', $theme = '')
{
    $templates_left = TemplatesLeft::where('ru_id', $ru_id)
        ->where('type', $type)
        ->where('seller_templates', $tem)
        ->where('theme', $theme);

    $templates_left = app(BaseRepository::class)->getToArrayFirst($templates_left);

    if ($templates_left && $templates_left['img_file']) {
        $templates_left['img_file'] = str_replace('../', '', $templates_left['img_file']);
        $templates_left['img_file'] = get_image_path($templates_left['img_file']);
    }
    return $templates_left;
}

/*删除模板
 *  */
function getDelDirAndFile($dirName)
{
    if (is_dir($dirName)) {
        if ($handle = opendir($dirName)) {
            while (false !== ($item = readdir($handle))) {
                if ($item != "." && $item != "..") {
                    if (is_dir("$dirName/$item")) {
                        getDelDirAndFile("$dirName/$item");
                    } else {
                        unlink("$dirName/$item");
                    }
                }
            }

            closedir($handle);

            return rmdir($dirName);
        }
    } else {
        return true;
    }
}

/*复制文件*/

function recurse_copy($src, $des, $type = 0)
{
    if (!is_dir($des)) {
        make_dir($des);
    }
    $dir = opendir($src);
    $format = ['png', 'gif', 'jpg', 'jpeg'];

    while (false !== ($file = readdir($dir))) {
        if (($file != '.') && ($file != '..')) {
            if (is_dir($src . '/' . $file)) {
                recurse_copy($src . '/' . $file, $des . '/' . $file);
            } else {
                if ($type == 0) {
                    copy($src . '/' . $file, $des . '/' . $file);
                    $prefix = substr($file, strrpos($file, '.') + 1);
                    if (in_array($prefix, $format)) {
                        $ossFile = str_replace(storage_public(), '', $des . '/' . $file);
                        app(DscRepository::class)->getOssAddFile([$ossFile]);
                    }
                } else {
                    $comtent = read_static_flie_cache($src . '/' . $file, '', '', 1);
                    $files = explode(".", $file);
                    $files_count = count($files) - (count($files) - 1);
                    $suffix_name = $files[$files_count];

                    if (count($files) > 2) {
                        $path = count($files) - 1;

                        $name = '';
                        if ($files[$path]) {
                            foreach ($files[$path] as $row) {
                                $name .= $row . ".";
                            }

                            $name = substr($name, 0, -1);
                        }

                        $file_path = explode("/", $name);
                        if ($file_path > 2) {
                            $path = count($file_path) - 1;
                            $cachename = $file_path[$path];
                        } else {
                            $cachename = $file_path[0];
                        }
                    } else {
                        $cachename = $files[0];
                    }
                    write_static_file_cache($cachename, $comtent, $suffix_name, $des . '/', 1);
                }
            }
        }
    }
    closedir($dir);
}

/*获取一个不重复的模板名称*/
function get_new_dir_name($ru_id = 0, $des = '')
{
    if ($des == '') {
        $des = storage_public(DATA_DIR . '/seller_templates/seller_tem_' . $ru_id);
    }
    if (!is_dir($des)) {
        return "backup_tpl_1";
    } else {
        $res = [];
        $dir = opendir($des);
        while (false !== ($file = readdir($dir))) {
            if (($file != '.') && ($file != '..')) {
                if (is_dir($des . "/" . $file)) {
                    $arr = explode('_', $file);
                    if (isset($arr[2]) && $arr[2]) {
                        $res[] = $arr[2];
                    }
                }
            }
        }
        closedir($dir);
        if ($res) {
            $suffix = MAX($res) + 1;
            return "backup_tpl_" . $suffix;
        } else {
            return "backup_tpl_1";
        }
    }
}

//获取相册图片列表
function getAlbumList($album_id = 0)
{
    $adminru = get_admin_ru_id();
    $filter['album_id'] = !empty($_REQUEST['album_id']) ? intval($_REQUEST['album_id']) : 0;
    $filter['sort_name'] = (!empty($_REQUEST['sort_name']) && $_REQUEST['sort_name'] != 'undefined') ? intval($_REQUEST['sort_name']) : 2;

    $row = PicAlbum::whereRaw(1);

    if ($album_id > 0) {
        $filter['album_id'] = $album_id;
    }

    if ($adminru['suppliers_id'] > 0) {
        $suppliers_id = GalleryAlbum::where('album_id', $filter['album_id'])->value('suppliers_id');

        if ($suppliers_id != $adminru['suppliers_id']) {
            $filter['album_id'] = GalleryAlbum::where('suppliers_id', $adminru['suppliers_id'])->value('album_id');
        }
        $row = $row->where('album_id', $filter['album_id']);

    } else {
        $album_info = GalleryAlbum::select('ru_id', 'suppliers_id')
            ->where('album_id', $filter['album_id']);
        $album_info = app(BaseRepository::class)->getToArrayFirst($album_info);

        if ($adminru['ru_id'] > 0 || ($album_info && $album_info['suppliers_id'] > 0)) {
            if ($adminru['ru_id'] > 0) {
                $row = $row->where('ru_id', $adminru['ru_id']);
            }
        }
        if ($filter['album_id'] > 0) {
            $row = $row->where('album_id', $filter['album_id']);
        }
    }

//    if ($filter['album_id'] > 0) {
//        $row = $row->where('album_id', $filter['album_id']);
//    }

    $res = $record_count = $row;

    $filter['record_count'] = $record_count->count();
    $filter = page_and_size($filter, 3);

    if ($filter['sort_name'] > 0) {
        switch ($filter['sort_name']) {
            case 1:
                $res = $res->orderBy('pic_id');
                break;

            case 2:
                $res = $res->orderBy('pic_id', 'desc');
                break;

            case 3:
                $res = $res->orderBy('pic_size');
                break;

            case 4:
                $res = $res->orderBy('pic_size', 'desc');
                break;

            case 5:
                $res = $res->orderBy('pic_name');
                break;

            case 6:
                $res = $res->orderBy('pic_name', 'desc');
                break;
        }
    }

    if ($filter['start'] > 0) {
        $res = $res->skip($filter['start']);
    }

    if ($filter['page_size'] > 0) {
        $res = $res->take($filter['page_size']);
    }

    $res = app(BaseRepository::class)->getToArrayGet($res);

    $arr = [];
    if ($res) {
        foreach ($res as $key => $row) {
            $row['pic_file'] = get_image_path($row['pic_file']);
            $row['pic_thumb'] = get_image_path($row['pic_thumb']);
            $row['pic_image'] = get_image_path($row['pic_image']);

            $arr[] = $row;
        }
    }

    $filter['page_arr'] = seller_page($filter, $filter['page'], 14);
    return ['list' => $arr, 'filter' => $filter];
}

/**
 * 首页广告位 - 商品列表
 */
function get_home_adv_goods_list($where = [])
{
    $row = Goods::where('is_on_sale', 1)
        ->where('is_delete', 0);

    if (isset($where['time'])) {
        $row = $row->where('promote_start_date', '<=', $where['time'])
            ->where('promote_end_date', '>=', $where['time'])
            ->where('promote_price', '>', 0);
    }

    if ($GLOBALS['_CFG']['review_goods'] == 1) {
        $row = $row->where('review_status', '>', 2);
    }

    if (isset($where['rs_id']) && $where['rs_id'] > 0) {
        $row = app(DscRepository::class)->getWhereRsid($row, 'user_id', $where['rs_id']);
    }

    $res = $record_count = $row;

    $filter['record_count'] = $record_count->count();

    $filter = page_and_size($filter);

    if ($filter['start'] > 0) {
        $res = $res->skip($filter['start']);
    }

    if ($filter['page_size'] > 0) {
        $res = $res->take($filter['page_size']);
    }

    $res = app(BaseRepository::class)->getToArrayGet($res);

    $filter['page_arr'] = seller_page($filter, $filter['page']);

    return ['list' => $res, 'filter' => $filter];
}

/**
 * 促销商品 - 商品列表
 */
function get_visual_promote_goods($where = [])
{
    $where['goods_ids'] = app(BaseRepository::class)->getExplode($where['goods_ids']);

    $CategoryRep = app(CategoryService::class);

    $row = Goods::whereRaw(1);

    if (isset($where['cat_id']) && $where['cat_id'] > 0) {
        $children = $CategoryRep->getCatListChildren($where['cat_id']);
        if ($children) {
            $row = $row->whereIn('cat_id', $children);
        }
    }

    if (isset($where['temp']) && isset($where['type']) && $where['type'] == 0 && $where['temp'] != 'h-seckill') {
        if ($where['goods_ids']) {
            $where['goods_ids'] = app(BaseRepository::class)->getExplode($where['goods_ids']);

            $row = $row->whereIn('goods_id', $where['goods_ids']);
        }
    }

    if (isset($where['brand_id']) && $where['brand_id'] > 0) {
        $row = $row->where('brand_id', $where['brand_id']);
    }

    if ($GLOBALS['_CFG']['review_goods'] == 1) {
        $row = $row->where('review_status', '>', 2);
    }

    if (isset($where['rs_id']) && $where['rs_id'] > 0) {
        $row = app(DscRepository::class)->getWhereRsid($row, 'user_id', $where['rs_id']);
    }

    //拼接条件
    if (isset($where['promotion_type']) && !empty($where['promotion_type'])) {
        if ($where['promotion_type'] == 'exchange') {
            if (isset($where['keyword']) && $where['keyword']) {
                $row = $row->where('goods_name', 'like', '%' . $where['keyword'] . '%');
            }

            $row = $row->where('is_delete', 0);

            $row = $row->whereHas('getExchangeGoods', function ($query) use ($where) {
                $query->where('is_exchange', 1);
            });

            $row = $row->with([
                'getExchangeGoods' => function ($query) {
                    $query->select('goods_id', 'exchange_integral');
                }
            ]);
        } elseif (isset($where['promotion_type']) && $where['promotion_type'] == 'presale') {
            if (isset($where['keyword']) && $where['keyword']) {
                $row = $row->where('goods_name', 'like', '%' . $where['keyword'] . '%');
            }

            $row = $row->whereHas('getPresaleActivity', function ($query) use ($where) {
                $query = $query->where('review_status', 3)
                    ->where('start_time', '<=', $where['time'])
                    ->where('end_time', '>=', $where['time'])
                    ->where('is_finished', 0);

                if (isset($where['keyword']) && $where['keyword']) {
                    $query = $query->where('act_name', 'like', '%' . $where['keyword'] . '%');
                }
            });

            $row = $row->with([
                'getPresaleActivity' => function ($query) {
                    $query->select('goods_id', 'act_name', 'end_time', 'start_time');
                }
            ]);
        } elseif (isset($where['promotion_type']) && $where['promotion_type'] == 'is_new') {
            $row = $row->where('is_new', 1)
                ->where('is_on_sale', 1)
                ->where('is_delete', 0);
        } elseif (isset($where['promotion_type']) && $where['promotion_type'] == 'is_best') {
            $row = $row->where('is_best', 1)
                ->where('is_on_sale', 1)
                ->where('is_delete', 0);
        } elseif (isset($where['promotion_type']) && $where['promotion_type'] == 'is_hot') {
            $row = $row->where('is_hot', 1)
                ->where('is_on_sale', 1)
                ->where('is_delete', 0);
        } else {
            if (isset($where['promotion_type']) && $where['promotion_type'] == 'snatch') {
                $where['act_type'] = GAT_SNATCH;
            } elseif (isset($where['promotion_type']) && $where['promotion_type'] == 'auction') {
                $where['act_type'] = GAT_AUCTION;
            } elseif (isset($where['promotion_type']) && $where['promotion_type'] == 'group_buy') {
                $where['act_type'] = GAT_GROUP_BUY;
            }

            if (isset($where['keyword']) && $where['keyword']) {
                $row = $row->where('goods_name', 'like', '%' . $where['keyword'] . '%');
            }

            $row = $row->whereHas('getGoodsActivity', function ($query) use ($where) {
                $query = $query->where('review_status', 3)
                    ->where('start_time', '<=', $where['time'])
                    ->where('end_time', '>=', $where['time'])
                    ->where('is_finished', 0);

                if (isset($where['act_type'])) {
                    $query = $query->where('act_type', $where['act_type']);
                }

                if (isset($where['keyword']) && $where['keyword']) {
                    $query = $query->where('act_name', 'like', '%' . $where['keyword'] . '%');
                }
            });

            $row = $row->with([
                'getGoodsActivity' => function ($query) {
                    $query->select('goods_id', 'act_name', 'start_time', 'end_time', 'ext_info');
                }
            ]);
        }
    } elseif ($where['temp'] == 'h-seckill') {
        $seckill = Seckill::selectRaw('GROUP_CONCAT(sec_id) AS sec_id')
            ->where('begin_time', '<=', $where['time'])
            ->where('acti_time', '>=', $where['time'])
            ->where('review_status', 3);
        $seckill = app(BaseRepository::class)->getToArrayFirst($seckill);

        $where['sec_id'] = $seckill ? explode(",", $seckill['sec_id']) : [];

        $row = $row->whereHas('getSeckillGoods', function ($query) use ($where) {
            $query = $query->where('tb_id', $where['time_bucket']);

            if (isset($where['type']) && $where['type'] == 0) {
                $query = $query->whereIn('id', $where['goods_ids']);
            }

            $query->whereHas('getSeckill', function ($query) use ($where) {
                $query->where('review_status', 3)
                    ->where('is_putaway', 1)
                    ->where('begin_time', '<', $where['time'])
                    ->where('acti_time', '>', $where['time'])
                    ->whereIn('sec_id', $where['sec_id']);
            });
        });

        $row = $row->where('is_on_sale', 1)
            ->where('is_delete', 0);

        $row = $row->with([
            'getSeckillGoods' => function ($query) use ($where) {
                $query->select('goods_id', 'sec_price', 'id')
                    ->whereIn('sec_id', $where['sec_id'])
                    ->where('tb_id', $where['time_bucket']);
            }
        ]);
    } else {
        if (isset($where['time'])) {
            $row = $row->where('promote_start_date', '<=', $where['time'])
                ->where('promote_end_date', '>=', $where['time'])
                ->where('promote_price', '>', 0);
        }

        $row = $row->where('is_on_sale', 1)
            ->where('is_delete', 0);

        if (isset($where['keyword']) && $where['keyword']) {
            $row = $row->where('goods_name', 'like', '%' . $where['keyword'] . '%');
        }
    }

    $res = $record_count = $row;

    $filter['record_count'] = $record_count->count();

    $filter = page_and_size($filter);

    if ($filter['start'] > 0) {
        $res = $res->skip($filter['start']);
    }

    if ($filter['page_size'] > 0) {
        $res = $res->take($filter['page_size']);
    }

    $res = app(BaseRepository::class)->getToArrayGet($res);

    if ($res) {
        foreach ($res as $key => $val) {
            $val = isset($val['get_exchange_goods']) ? app(BaseRepository::class)->getArrayMerge($val, $val['get_exchange_goods']) : $val;
            $val = isset($val['get_presale_activity']) ? app(BaseRepository::class)->getArrayMerge($val, $val['get_presale_activity']) : $val;
            $val = isset($val['get_goods_activity']) ? app(BaseRepository::class)->getArrayMerge($val, $val['get_goods_activity']) : $val;
            $val = isset($val['get_seckill_goods']) ? app(BaseRepository::class)->getArrayMerge($val, $val['get_seckill_goods']) : $val;

            $res[$key] = $val;
        }
    }

    $filter['page_arr'] = seller_page($filter, $filter['page']);

    return ['list' => $res, 'filter' => $filter];
}

/**
 * 商品模块 - 商品列表
 */
function get_content_changed_goods($where = [])
{
    $where['goods_ids'] = app(BaseRepository::class)->getExplode($where['goods_ids']);

    $row = Goods::where('is_delete', 0)->where('is_on_sale', 1);

    if ($GLOBALS['_CFG']['review_goods'] == 1) {
        $row = $row->where('review_status', '>', 2);
    }

    if (isset($where['user_id'])) {
        $row = $row->where('user_id', $where['user_id']);
    }

    if (isset($where['search_type']) && $where['search_type'] != 'goods' && isset($where['is_on_sale'])) {
        $row = $row->where('is_on_sale', $where['is_on_sale']);
    }

    if (isset($where['search_type']) && in_array($where['search_type'], ['goods', 'package'])) {
        $seller_id = -2;
        if (isset($where['goods_id']) && $where['goods_id'] > 0) {
            $seller_id = Goods::where('goods_id', $where['goods_id'])->value('user_id');
        } elseif (isset($where['ru_id']) && $where['ru_id'] != '-1') {
            $seller_id = $where['ru_id'];
        } elseif (isset($where['seller_id'])) {
            $seller_id = $where['seller_id'];
        }

        if ($seller_id > -2) {
            $row = $row->where('user_id', $seller_id);
        }

        if ($where['search_type'] == 'package') {
            $row = $row->where('model_attr', 0); // 超值礼包只支持普通货品模式
        }
    } else {
        if (isset($where['rs_id']) && $where['rs_id'] > 0) {
            $row = app(DscRepository::class)->getWhereRsid($row, 'user_id', $where['rs_id']);
        } else {
            if (isset($where['ru_id'])) {
                if ($where['ru_id'] != -1) {
                    $row = $row->where('user_id', $where['ru_id']);
                }
            }
        }

        if (isset($where['rs_id'])) {
            $row = $row->where('is_on_sale', 1);
        }
    }

    if (isset($where['cat_id']) && isset($where['cat_id'][0]) && $where['cat_id'][0] > 0) {
        $CategoryRep = app(CategoryService::class);
        $children = $CategoryRep->getCatListChildren($where['cat_id'][0]);

        if ($children) {
            $row = $row->whereIn('cat_id', $children);
        }
    }

    if (isset($where['brand_id']) && $where['brand_id'] > 0) {
        $row = $row->where('brand_id', $where['brand_id']);
    }

    if (isset($where['keyword']) && $where['keyword']) {
        $row = $row->where('goods_name', 'like', '%' . $where['keyword'] . '%');
    }

    if (isset($where['goods_ids']) && isset($where['type']) && $where['goods_ids'] && $where['type'] == '0') {
        $row = $row->whereIn('goods_id', $where['goods_ids']);
    }

    $res = $record_count = $row;

    $filter = [];
    if ($where['is_page'] == 1) {
        $filter['record_count'] = $record_count->count();

        $filter = page_and_size($filter);
    }

    switch ($where['sort_order']) {
        case 1:
            $res = $res->orderBy("add_time");
            break;

        case 2:
            $res = $res->orderBy("add_time", 'desc');
            break;

        case 3:
            $res = $res->orderBy("sort_order");
            break;

        case 4:
            $res = $res->orderBy("sort_order", 'desc');
            break;

        case 5:
            $res = $res->orderBy("goods_name");
            break;

        case 6:
            $res = $res->orderBy("goods_name", 'desc');
            break;
        default:
            $res = $res->orderByRaw("sort_order, sales_volume desc");
            break;
    }

    if ($where['is_page'] == 1) {
        if ($filter['start'] > 0) {
            $res = $res->skip($filter['start']);
        }

        if ($filter['page_size'] > 0) {
            $res = $res->take($filter['page_size']);
        }
    }

    $res = app(BaseRepository::class)->getToArrayGet($res);

    if ($where['is_page'] == 1) {
        $filter['page_arr'] = seller_page($filter, $filter['page']);
        return ['list' => $res, 'filter' => $filter];
    } else {
        return $res;
    }
}

//重置选择的数据
function resetBarnd($brand_id = [], $table = 'goods', $category = 'goods_id')
{
    if ($brand_id) {
        $brand_id = app(BaseRepository::class)->getExplode($brand_id);

        if ($table == 'goods') {
            $adminru = get_admin_ru_id();

            $res = Goods::where('is_on_sale', 1)
                ->where('is_delete', 0);

            $res = $res->whereIn($category, $brand_id);

            if ($GLOBALS['_CFG']['review_goods'] == 1) {
                $res = $res->where('review_status', '>', 2);
            }

            if ($GLOBALS['_CFG']['region_store_enabled'] == 1) {
                $res = app(DscRepository::class)->getWhereRsid($res, 'user_id', $adminru['rs_id']);
            } else {
                if ($adminru['ru_id'] > 0) {
                    $res = $res->where('user_id', $adminru['ru_id']);
                }
            }

            $res = app(BaseRepository::class)->getToArrayGet($res);
            $res = app(BaseRepository::class)->getKeyPluck($res, $category);
        } elseif ($table == 'brand') {
            $res = Brand::whereIn('brand_id', $brand_id)
                ->where('is_show', 1);

            $res = $res->whereHas('getBrandExtend', function ($query) {
                $query->where('is_recommend', 1);
            });

            $res = app(BaseRepository::class)->getToArrayGet($res);
            $res = app(BaseRepository::class)->getKeyPluck($res, 'brand_id');
        } elseif ($table == 'seckill') {
            $time = gmtime();
            $where = [
                'brand_id' => $brand_id,
                'time' => $time
            ];
            $res = Goods::where('is_on_sale', 1)
                ->where('is_delete', 0);

            if ($GLOBALS['_CFG']['review_goods'] == 1) {
                $res = $res->where('review_status', '>', 2);
            }

            $res = $res->whereHas('getSeckillGoods', function ($query) use ($where) {
                $query->whereIn('id', $where['brand_id'])
                    ->whereHas('getSeckill', function ($query) use ($where) {
                        $query->where('review_status', 3)
                            ->where('is_putaway', 1)
                            ->where('begin_time', '<', $where['time'])
                            ->where('acti_time', '>', $where['time']);
                    });
            });

            $res = $res->with([
                'getSeckillGoods' => function ($query) {
                    $query->select('goods_id', 'id');
                }
            ]);

            $res = app(BaseRepository::class)->getToArrayGet($res);

            if ($res) {
                foreach ($res as $key => $row) {
                    $row = app(BaseRepository::class)->getArrayMerge($row, $row['get_seckill_goods']);

                    $res[$key] = $row;
                }
            }

            $res = app(BaseRepository::class)->getKeyPluck($res, 'id');
        }

        if (!empty($res)) {
            return app(BaseRepository::class)->getImplode($res);
        } else {
            return '';
        }
    } else {
        return '';
    }
}

//去除字符串中的特殊字符
function strFilter($str, $type = '')
{
    $str = str_replace('`', '', $str);
    $str = str_replace('·', '', $str);
    $str = str_replace('~', '', $str);
    $str = str_replace('!', '', $str);
    $str = str_replace('！', '', $str);
    $str = str_replace('@', '', $str);
    $str = str_replace('#', '', $str);
    $str = str_replace('$', '', $str);
    $str = str_replace('￥', '', $str);
    $str = str_replace('%', '', $str);
    $str = str_replace('^', '', $str);
    $str = str_replace('……', '', $str);
    $str = str_replace('&', '', $str);
    $str = str_replace('*', '', $str);
    $str = str_replace('(', '', $str);
    $str = str_replace(')', '', $str);
    $str = str_replace('（', '', $str);
    $str = str_replace('）', '', $str);
    $str = str_replace('-', '', $str);
    $str = str_replace('_', '', $str);
    $str = str_replace('——', '', $str);
    $str = str_replace('+', '', $str);
    $str = str_replace('=', '', $str);
    $str = str_replace('|', '', $str);
    $str = str_replace('\\', '', $str);
    $str = str_replace('[', '', $str);
    $str = str_replace(']', '', $str);
    $str = str_replace('【', '', $str);
    $str = str_replace('】', '', $str);
    $str = str_replace('{', '', $str);
    $str = str_replace('}', '', $str);
    $str = str_replace(';', '', $str);
    $str = str_replace('；', '', $str);
    $str = str_replace(':', '', $str);
    $str = str_replace('：', '', $str);
    $str = str_replace('\'', '', $str);
    $str = str_replace('"', '', $str);
    $str = str_replace('“', '', $str);
    $str = str_replace('”', '', $str);
    $str = str_replace(',', '', $str);
    $str = str_replace('，', '', $str);
    $str = str_replace('<', '', $str);
    $str = str_replace('>', '', $str);
    $str = str_replace('《', '', $str);
    $str = str_replace('》', '', $str);
    $str = str_replace('。', '', $str);
    $str = str_replace('/', '', $str);
    $str = str_replace('、', '', $str);
    $str = str_replace('?', '', $str);
    $str = str_replace('？', '', $str);
    //标题广告中小数点不过滤
    if (empty($type)) {
        $str = str_replace('.', '', $str);
    }
    return trim($str);
}

//获取楼层模板广告模式数组
function get_floor_style($mode = '')
{
    $arr = [];

    switch ($mode) {
        case 'homeFloor':
            $arr = [
                '0' => '1,2,3',
                '1' => '1,2,3',
                '2' => '2,3',
                '3' => '1,2,3'
            ];
            break;

        case 'homeFloorModule':
            $arr = [
                '0' => '1,3',
                '1' => '1,3',
                '2' => '1,3',
                '3' => '1,3'
            ];
            break;

        case 'homeFloorThree':
            $arr = [
                '0' => '2',
                '1' => '1,2,3',
                '2' => '1,3',
                '3' => '2,3'
            ];
            break;

        case 'homeFloorFour':
            $arr = [
                '0' => '2',
                '1' => '1',
                '2' => '2',
                '3' => ''
            ];
            break;

        case 'homeFloorFive':
            $arr = [
                '0' => '1,2',
                '1' => '1,2,3',
                '2' => '1,2,3',
                '3' => '1,2,3',
                '4' => '1,2,3'
            ];
            break;

        case 'homeFloorSix':
            $arr = [
                '0' => '1,2',
                '1' => '1,2',
                '2' => '1,2',
                '3' => '1'
            ];
            break;

        case 'homeFloorSeven':
            $arr = [
                '0' => '1,2',
                '1' => '1,2',
                '2' => '1,2',
                '3' => '1,2',
                '4' => '1,2'
            ];
            break;

        case 'homeFloorEight':
            $arr = [
                '0' => '1,2',
                '1' => '1,2',
                '2' => '1',
                '3' => '1,2',
                '4' => '1,2'
            ];
            break;

        case 'homeFloorNine':
            $arr = [
                '0' => '1,2,3',
                '1' => '1,2,3',
                '2' => '1,2,3',
                '3' => '1,3'
            ];
            break;

        case 'homeFloorTen':
            $arr = [
                '0' => '1,2',
                '1' => '1,2',
                '2' => '1,2',
                '3' => '1'
            ];
            break;

        case 'storeOneFloor1':
            $arr = [
                '0' => '1',
                '1' => '2,3',
                '2' => '2',
                '3' => '',
            ];
            break;


        case 'storeTwoFloor1':
            $arr = [
                '0' => '2',
                '1' => '1,2',
                '2' => '2'
            ];
            break;

        case 'storeThreeFloor1':
            $arr = [
                '0' => '2',
                '1' => '1,2',
                '2' => '2',
                '3' => ''
            ];
            break;

        case 'storeFourFloor1':
            $arr = [
                '0' => '2',
                '1' => '',
                '2' => '1,2',
                '3' => ''
            ];
            break;

        case 'storeFiveFloor1':
            $arr = [
                '0' => '',
                '1' => '',
                '2' => '2',
                '3' => '2'
            ];
            break;

        case 'topicOneFloor':
            $arr = [
                '0' => '2',
                '1' => '',
                '2' => ''
            ];
            break;

        case 'topicTwoFloor':
            $arr = [
                '0' => '2',
                '1' => '2',
                '2' => '',
                '3' => ''
            ];
            break;

        case 'topicThreeFloor':
            $arr = [
                '0' => '2',
                '1' => '2',
                '2' => ''
            ];
            break;
        case 'CMS_ADV':
            $arr = [
                '0' => '1',
                '1' => '1,2',
                '2' => '1,2'
            ];
            break;

        case 'FhomeFloorModule':
            $arr = [
                '0' => '1,3',
                '1' => '1,3'
            ];
            break;
    }

    return $arr;
}

//获取楼层模板不同广告模式  不同广告  不同广告数量数组
function getAdvNum($mode = '', $floorMode = 0)
{
    $arr = [];

    switch ($mode) {
        case 'homeFloor':
            $arr1 = [
                'leftBanner' => '3',
                'leftAdv' => '2',
                'rightAdv' => '5'
            ];
            $arr2 = [
                'leftBanner' => '3',
                'leftAdv' => '2',
                'rightAdv' => '5'
            ];
            $arr3 = [
                'leftAdv' => '2',
                'rightAdv' => '5'
            ];
            $arr4 = [
                'leftBanner' => '3',
                'leftAdv' => '2',
                'rightAdv' => '5'
            ];

            if ($floorMode == 1) {
                $arr = $arr1;
            } elseif ($floorMode == 2) {
                $arr = $arr2;
            } elseif ($floorMode == 3) {
                $arr = $arr3;
            } elseif ($floorMode == 4) {
                $arr = $arr4;
            } else {
                $arr[1] = $arr1;
                $arr[2] = $arr2;
                $arr[3] = $arr3;
                $arr[4] = $arr4;
            }
            break;

        case 'homeFloorModule':
            $arr1 = [
                'leftBanner' => '3',
                'rightAdv' => '4'
            ];
            $arr2 = [
                'leftBanner' => '3',
                'rightAdv' => '3'
            ];
            $arr3 = [
                'leftBanner' => '3',
                'rightAdv' => '3'
            ];
            $arr4 = [
                'leftBanner' => '3',
                'rightAdv' => '2'
            ];

            if ($floorMode == 1) {
                $arr = $arr1;
            } elseif ($floorMode == 2) {
                $arr = $arr2;
            } elseif ($floorMode == 3) {
                $arr = $arr3;
            } elseif ($floorMode == 4) {
                $arr = $arr4;
            } else {
                $arr[1] = $arr1;
                $arr[2] = $arr2;
                $arr[3] = $arr3;
                $arr[4] = $arr4;
            }
            break;

        case 'homeFloorThree':
            $arr1 = [
                'leftAdv' => '5'
            ];
            $arr2 = [
                'leftBanner' => '3',
                'leftAdv' => '1',
                'rightAdv' => '6'
            ];
            $arr3 = [
                'leftBanner' => '3',
                'rightAdv' => '8'
            ];
            $arr4 = [
                'leftAdv' => '2',
                'rightAdv' => '8'
            ];

            if ($floorMode == 1) {
                $arr = $arr1;
            } elseif ($floorMode == 2) {
                $arr = $arr2;
            } elseif ($floorMode == 3) {
                $arr = $arr3;
            } elseif ($floorMode == 4) {
                $arr = $arr4;
            } else {
                $arr[1] = $arr1;
                $arr[2] = $arr2;
                $arr[3] = $arr3;
                $arr[4] = $arr4;
            }
            break;

        case 'homeFloorFour':
            $arr1 = [
                'leftAdv' => '2'
            ];
            $arr2 = [
                'leftBanner' => '3'
            ];
            $arr3 = [
                'leftAdv' => '2'
            ];
            $arr4 = [];

            if ($floorMode == 1) {
                $arr = $arr1;
            } elseif ($floorMode == 2) {
                $arr = $arr2;
            } elseif ($floorMode == 3) {
                $arr = $arr3;
            } elseif ($floorMode == 4) {
                $arr = $arr4;
            } else {
                $arr[1] = $arr1;
                $arr[2] = $arr2;
                $arr[3] = $arr3;
                $arr[4] = $arr4;
            }
            break;

        case 'homeFloorFive':
            $arr1 = [
                'leftBanner' => '3',
                'leftAdv' => '3'
            ];
            $arr2 = [
                'leftBanner' => '3',
                'leftAdv' => '3',
                'rightAdv' => '3'
            ];
            $arr3 = [
                'leftBanner' => '3',
                'leftAdv' => '3',
                'rightAdv' => '2'
            ];
            $arr4 = [
                'leftBanner' => '3',
                'leftAdv' => '3',
                'rightAdv' => '1'
            ];
            $arr5 = [
                'leftBanner' => '3',
                'leftAdv' => '3',
                'rightAdv' => '2'
            ];

            if ($floorMode == 1) {
                $arr = $arr1;
            } elseif ($floorMode == 2) {
                $arr = $arr2;
            } elseif ($floorMode == 3) {
                $arr = $arr3;
            } elseif ($floorMode == 4) {
                $arr = $arr4;
            } elseif ($floorMode == 5) {
                $arr = $arr5;
            } else {
                $arr[1] = $arr1;
                $arr[2] = $arr2;
                $arr[3] = $arr3;
                $arr[4] = $arr4;
                $arr[5] = $arr5;
            }
            break;

        case 'homeFloorSix':
            $arr1 = [
                'leftBanner' => '3',
                'leftAdv' => '4'
            ];
            $arr2 = [
                'leftBanner' => '3',
                'leftAdv' => '2'
            ];
            $arr3 = [
                'leftBanner' => '3',
                'leftAdv' => '1'
            ];
            $arr4 = [
                'leftBanner' => '3'
            ];

            if ($floorMode == 1) {
                $arr = $arr1;
            } elseif ($floorMode == 2) {
                $arr = $arr2;
            } elseif ($floorMode == 3) {
                $arr = $arr3;
            } elseif ($floorMode == 4) {
                $arr = $arr4;
            } else {
                $arr[1] = $arr1;
                $arr[2] = $arr2;
                $arr[3] = $arr3;
                $arr[4] = $arr4;
            }
            break;

        case 'homeFloorSeven':
            $arr1 = [
                'leftBanner' => '3',
                'leftAdv' => '1'
            ];
            $arr2 = [
                'leftBanner' => '3',
                'leftAdv' => '1'
            ];
            $arr3 = [
                'leftBanner' => '3',
                'leftAdv' => '1'
            ];
            $arr4 = [
                'leftBanner' => '3',
                'leftAdv' => '1'
            ];
            $arr5 = [
                'leftBanner' => '3',
                'leftAdv' => '1'
            ];

            if ($floorMode == 1) {
                $arr = $arr1;
            } elseif ($floorMode == 2) {
                $arr = $arr2;
            } elseif ($floorMode == 3) {
                $arr = $arr3;
            } elseif ($floorMode == 4) {
                $arr = $arr4;
            } elseif ($floorMode == 5) {
                $arr = $arr5;
            } else {
                $arr[1] = $arr1;
                $arr[2] = $arr2;
                $arr[3] = $arr3;
                $arr[4] = $arr4;
                $arr[5] = $arr5;
            }
            break;

        case 'homeFloorEight':
            $arr1 = [
                'leftBanner' => '3',
                'leftAdv' => '4'
            ];
            $arr2 = [
                'leftBanner' => '3',
                'leftAdv' => '1'
            ];
            $arr3 = [
                'leftBanner' => '3',
                'leftAdv' => '1'
            ];
            $arr4 = [
                'leftBanner' => '3',
                'leftAdv' => '1'
            ];
            $arr5 = [
                'leftBanner' => '3',
                'leftAdv' => '2'
            ];

            if ($floorMode == 1) {
                $arr = $arr1;
            } elseif ($floorMode == 2) {
                $arr = $arr2;
            } elseif ($floorMode == 3) {
                $arr = $arr3;
            } elseif ($floorMode == 4) {
                $arr = $arr4;
            } elseif ($floorMode == 5) {
                $arr = $arr5;
            } else {
                $arr[1] = $arr1;
                $arr[2] = $arr2;
                $arr[3] = $arr3;
                $arr[4] = $arr4;
                $arr[5] = $arr5;
            }
            break;

        case 'homeFloorNine':
            $arr1 = [
                'leftBanner' => '3',
                'leftAdv' => '3',
                'rightAdv' => '4'
            ];
            $arr2 = [
                'leftBanner' => '3',
                'leftAdv' => '2',
                'rightAdv' => '4'
            ];
            $arr3 = [
                'leftBanner' => '3',
                'leftAdv' => '1',
                'rightAdv' => '6'
            ];
            $arr4 = [
                'leftBanner' => '3',
                'rightAdv' => '8'
            ];

            if ($floorMode == 1) {
                $arr = $arr1;
            } elseif ($floorMode == 2) {
                $arr = $arr2;
            } elseif ($floorMode == 3) {
                $arr = $arr3;
            } elseif ($floorMode == 4) {
                $arr = $arr4;
            } else {
                $arr[1] = $arr1;
                $arr[2] = $arr2;
                $arr[3] = $arr3;
                $arr[4] = $arr4;
            }
            break;

        case 'homeFloorTen':
            $arr1 = [
                'leftBanner' => '3',
                'leftAdv' => '1'
            ];
            $arr2 = [
                'leftBanner' => '3',
                'leftAdv' => '1'
            ];
            $arr3 = [
                'leftBanner' => '3',
                'leftAdv' => '2'
            ];
            $arr4 = [
                'leftBanner' => '3'
            ];

            if ($floorMode == 1) {
                $arr = $arr1;
            } elseif ($floorMode == 2) {
                $arr = $arr2;
            } elseif ($floorMode == 3) {
                $arr = $arr3;
            } elseif ($floorMode == 4) {
                $arr = $arr4;
            } else {
                $arr[1] = $arr1;
                $arr[2] = $arr2;
                $arr[3] = $arr3;
                $arr[4] = $arr4;
            }
            break;

        case 'storeOneFloor1':
            $arr1 = [
                'leftBanner' => '3'
            ];
            $arr2 = [
                'leftAdv' => '3',
                'rightAdv' => '4'
            ];
            $arr3 = [
                'leftAdv' => '1'
            ];
            $arr4 = [];

            if ($floorMode == 1) {
                $arr = $arr1;
            } elseif ($floorMode == 2) {
                $arr = $arr2;
            } elseif ($floorMode == 3) {
                $arr = $arr3;
            } elseif ($floorMode == 3) {
                $arr = $arr4;
            } else {
                $arr[1] = $arr1;
                $arr[2] = $arr2;
                $arr[3] = $arr3;
                $arr[4] = $arr4;
            }
            break;

        case 'storeTwoFloor1':
            $arr1 = [
                'leftAdv' => '4'
            ];
            $arr2 = [
                'leftBanner' => '1',
                'leftAdv' => '3'
            ];
            $arr3 = [
                'leftAdv' => '6'
            ];

            if ($floorMode == 1) {
                $arr = $arr1;
            } elseif ($floorMode == 2) {
                $arr = $arr2;
            } elseif ($floorMode == 3) {
                $arr = $arr3;
            } else {
                $arr[1] = $arr1;
                $arr[2] = $arr2;
                $arr[3] = $arr3;
            }
            break;

        case 'storeThreeFloor1':
            $arr1 = [
                'leftAdv' => '8'
            ];
            $arr2 = [
                'leftBanner' => '3',
                'leftAdv' => '3'
            ];
            $arr3 = [
                'leftAdv' => '3'
            ];
            $arr4 = [];

            if ($floorMode == 1) {
                $arr = $arr1;
            } elseif ($floorMode == 2) {
                $arr = $arr2;
            } elseif ($floorMode == 3) {
                $arr = $arr3;
            } elseif ($floorMode == 4) {
                $arr = $arr4;
            } else {
                $arr[1] = $arr1;
                $arr[2] = $arr2;
                $arr[3] = $arr3;
                $arr[4] = $arr4;
            }
            break;

        case 'storeFourFloor1':
            $arr1 = [
                'leftAdv' => '3',
            ];
            $arr2 = [];
            $arr3 = [
                'leftBanner' => '1',
                'leftAdv' => '2'
            ];
            $arr4 = [];

            if ($floorMode == 1) {
                $arr = $arr1;
            } elseif ($floorMode == 2) {
                $arr = $arr2;
            } elseif ($floorMode == 3) {
                $arr = $arr3;
            } elseif ($floorMode == 4) {
                $arr = $arr4;
            } else {
                $arr[1] = $arr1;
                $arr[2] = $arr2;
                $arr[3] = $arr3;
                $arr[4] = $arr4;
            }
            break;

        case 'storeFiveFloor1':
            $arr1 = [
            ];
            $arr2 = [
            ];
            $arr3 = [
                'leftAdv' => '6'
            ];
            $arr4 = [
                'leftAdv' => '9'
            ];

            if ($floorMode == 1) {
                $arr = $arr1;
            } elseif ($floorMode == 2) {
                $arr = $arr2;
            } elseif ($floorMode == 3) {
                $arr = $arr3;
            } elseif ($floorMode == 4) {
                $arr = $arr4;
            } else {
                $arr[1] = $arr1;
                $arr[2] = $arr2;
                $arr[3] = $arr3;
                $arr[4] = $arr4;
            }
            break;

        case 'topicOneFloor':
            $arr1 = [
                'leftAdv' => '4'
            ];
            $arr2 = [];
            $arr3 = [];

            if ($floorMode == 1) {
                $arr = $arr1;
            } elseif ($floorMode == 2) {
                $arr = $arr2;
            } elseif ($floorMode == 3) {
                $arr = $arr3;
            } else {
                $arr[1] = $arr1;
                $arr[2] = $arr2;
                $arr[3] = $arr3;
            }
            break;

        case 'topicTwoFloor':
            $arr1 = [
                'leftAdv' => '5'
            ];
            $arr2 = [
                'leftAdv' => '1'
            ];
            $arr3 = [];
            $arr4 = [];

            if ($floorMode == 1) {
                $arr = $arr1;
            } elseif ($floorMode == 2) {
                $arr = $arr2;
            } elseif ($floorMode == 3) {
                $arr = $arr3;
            } elseif ($floorMode == 4) {
                $arr = $arr4;
            } else {
                $arr[1] = $arr1;
                $arr[2] = $arr2;
                $arr[3] = $arr3;
                $arr[4] = $arr3;
            }
            break;

        case 'topicThreeFloor':
            $arr1 = [
                'leftAdv' => '1'
            ];
            $arr2 = [
                'leftAdv' => '10'
            ];
            $arr3 = [];

            if ($floorMode == 1) {
                $arr = $arr1;
            } elseif ($floorMode == 2) {
                $arr = $arr2;
            } elseif ($floorMode == 3) {
                $arr = $arr3;
            } else {
                $arr[1] = $arr1;
                $arr[2] = $arr2;
                $arr[3] = $arr3;
            }
            break;

        case 'CMS_ADV':
            $arr1 = [
                'leftBanner' => '3'
            ];
            $arr2 = [
                'leftBanner' => '3',
                'leftAdv' => '2'
            ];
            $arr3 = [
                'leftBanner' => '3',
                'leftAdv' => '1'
            ];
            if ($floorMode == 1) {
                $arr = $arr1;
            } elseif ($floorMode == 2) {
                $arr = $arr2;
            } else {
                $arr = $arr3;
            }
            break;

        case 'FhomeFloorModule':
            $arr1 = [
                'leftBanner' => '1',
                'rightAdv' => '7'
            ];
            $arr2 = [
                'leftBanner' => '2',
                'rightAdv' => '8'
            ];

            if ($floorMode == 1) {
                $arr = $arr1;
            } elseif ($floorMode == 2) {
                $arr = $arr2;
            } else {
                $arr[1] = $arr1;
                $arr[2] = $arr2;
            }
            break;
    }

    return $arr;
}

/**
 * 下载OSS上面模板文件
 */
function get_down_oss_template($list)
{
    $bucket_info = app(DscRepository::class)->getBucketInfo();

    if ($list && $bucket_info['endpoint']) {
        foreach ($list as $key => $row) {

            if (is_dir(storage_public($row))) {
                continue;
            }

            if ($row) {
                $row = trim($row);

                $file = explode('/', $row);
                $count = count($file);

                $file_name = $file[$count - 1];
                $directory = str_replace($file_name, '', $row);

                $path = storage_public($directory);
                if (!file_exists($path)) {
                    make_dir($path);
                }

                get_http_basename($bucket_info['endpoint'] . $row, $path);

                $list[$key] = $bucket_info['endpoint'] . $row;
            }
        }
    }

    return $list;
}

/**
 * 模板列表
 * @return  array
 */
function template_mall_list()
{
    $result = get_filter();
    if ($result === false) {
        /* 初始化分页参数 */
        $filter = [];
        $filter['temp_mode'] = empty($_REQUEST['temp_mode']) ? 0 : intval($_REQUEST['temp_mode']);

        $row = TemplateMall::whereRaw(1);

        if ($filter['temp_mode'] > 0) {
            $temp_mode = $filter['temp_mode'];
            if ($filter['temp_mode'] == 2) {
                $temp_mode = 0;
            }

            $row = $row->where('temp_mode', $temp_mode);
        }

        $res = $record_count = $row;

        /* 查询记录总数，计算分页数 */
        $filter['record_count'] = $record_count->count();

        $filter = page_and_size($filter);

        /* 查询记录 */

        if ($filter['start'] > 0) {
            $res = $res->skip($filter['start']);
        }

        if ($filter['page_size'] > 0) {
            $res = $res->take($filter['page_size']);
        }

        $res = app(BaseRepository::class)->getToArrayGet($res);
    } else {
        $sql = $result['sql'];
        $filter = $result['filter'];

        $res = [];
    }

    $arr = [];
    if ($res) {
        foreach ($res as $rows) {
            $seller_template_info = [
                'screenshot' => '',
                'template' => '',
                'name' => '',
                'uri' => '',
                'desc' => '',
                'version' => '',
                'author' => '',
                'author_uri' => ''
            ];

            //获取模板信息
            if ($rows['temp_code']) {
                $seller_template_info = get_seller_template_info($rows['temp_code']);
            }

            //赋值
            $rows['screenshot'] = isset($seller_template_info['screenshot']) ? $seller_template_info['screenshot'] : '';
            $rows['template'] = isset($seller_template_info['template']) ? $seller_template_info['template'] : '';
            $rows['name'] = isset($seller_template_info['name']) ? $seller_template_info['name'] : '';
            $rows['uri'] = isset($seller_template_info['uri']) ? $seller_template_info['uri'] : '';
            $rows['desc'] = isset($seller_template_info['desc']) ? $seller_template_info['desc'] : '';
            $rows['version'] = isset($seller_template_info['version']) ? $seller_template_info['version'] : '';
            $rows['author'] = isset($seller_template_info['author']) ? $seller_template_info['author'] : '';
            $rows['author_uri'] = isset($seller_template_info['author_uri']) ? $seller_template_info['author_uri'] : '';
            $rows['code'] = $rows['temp_code'];
            $rows['temp_cost'] = price_format($rows['temp_cost']);
            if ($rows['add_time'] > 0) {
                $rows['add_time'] = local_date('Y-m-d H:i:s', $rows['add_time']);
            }

            $arr[] = $rows;
        }
    }

    return ['list' => $arr, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']];
}

//导入模板
function Import_temp($suffix = '', $new_suffix = '', $ru_id = 0)
{
    $dir = storage_public('data/seller_templates/seller_tem_' . $ru_id . "/" . $new_suffix);//新模板目录
    $file_html = storage_public('data/seller_templates/seller_tem/' . $suffix); //默认模板目录

    if (!is_dir($dir)) {
        make_dir($dir);
    }
    return recurse_copy($file_html, $dir);
}

//获取获取商家模板支付使用列表
function get_template_apply_list()
{
    $result = get_filter();
    if ($result === false) {
        $adminru = get_admin_ru_id();

        $filter['pay_starts'] = empty($_REQUEST['pay_starts']) ? '-1' : intval($_REQUEST['pay_starts']);
        $filter['apply_sn'] = !empty($_REQUEST['apply_sn']) ? trim($_REQUEST['apply_sn']) : '-1';

        $row = SellerTemplateApply::whereRaw(1);

        if ($adminru['ru_id'] > 0) {
            $row = $row->where('ru_id', $adminru['ru_id']);
        }

        if ($filter['pay_starts'] != -1) {
            if ($filter['pay_starts'] == 2) {
                $filter['pay_starts'] = 0;
            }

            $row = $row->where('pay_status', $filter['pay_starts']);
        }

        if ($filter['apply_sn'] != -1) {
            $row = $row->where('apply_sn', $filter['apply_sn']);
        }

        $res = $record_count = $row;

        /* 初始化分页参数 */
        $filter = [];
        /* 查询记录总数，计算分页数 */
        $filter['record_count'] = $record_count->count();

        $filter = page_and_size($filter);

        /* 查询记录 */

        $res = $res->with([
            'getPayment' => function ($query) {
                $query->select('pay_id', 'pay_name');
            }
        ]);

        $res = $res->orderBy('add_time', 'desc');

        if ($filter['start'] > 0) {
            $res = $res->skip($filter['start']);
        }

        if ($filter['page_size'] > 0) {
            $res = $res->take($filter['page_size']);
        }

        $res = app(BaseRepository::class)->getToArrayGet($res);
    } else {
        $filter = $result['filter'];
    }

    $arr = [];
    if ($res) {
        foreach ($res as $rows) {
            $seller_template_info = [
                'name' => ''
            ];

            if ($rows['temp_code']) {
                $seller_template_info = get_seller_template_info($rows['temp_code']);
            }

            $rows['name'] = $seller_template_info['name'];
            $rows['total_amount'] = price_format($rows['total_amount']);
            $rows['pay_fee'] = price_format($rows['pay_fee']);
            if ($rows['add_time'] > 0) {
                $rows['add_time'] = local_date('Y-m-d H:i:s', $rows['add_time']);
            }
            if ($rows['pay_time'] > 0) {
                $rows['pay_time'] = local_date('Y-m-d H:i:s', $rows['pay_time']);
            }

            $rows['pay_name'] = $rows['get_payment'] ? $rows['get_payment']['pay_name'] : '';
            $rows['shop_name'] = app(MerchantCommonService::class)->getShopName($rows['ru_id'], 1);
            $arr[] = $rows;
        }
    }

    return ['list' => $arr, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']];
}

/**
 * 首页可视化
 * 下载OSS模板文件
 */
function get_down_hometemplates($suffix = '')
{
    if ($GLOBALS['_CFG']['open_oss'] && $GLOBALS['_CFG']['server_model'] && !empty($suffix)) {
        if (!file_exists(storage_public(DATA_DIR . '/sc_file/hometemplates/' . $suffix . ".php"))) {
            /* 下载目录下文件 */
            $oss_list = app(DscRepository::class)->getOssListFile(['prefix' => DATA_DIR . '/home_templates/' . $GLOBALS['_CFG']['template'] . '/' . $suffix . '/']);

            if (!is_array($oss_list)) {
                $oss_list = dsc_decode($oss_list, true);
                get_down_oss_template($oss_list['list']);
            } else {
                get_down_oss_template($oss_list);
            }

            /* 下载目录下文件 */
            $oss_bonusadv_list = app(DscRepository::class)->getOssListFile(['prefix' => DATA_DIR . '/home_templates/' . $GLOBALS['_CFG']['template'] . '/' . $suffix . '/images/bonusadv/']);

            if (!is_array($oss_list)) {
                $oss_bonusadv_list = dsc_decode($oss_bonusadv_list, true);
                get_down_oss_template($oss_bonusadv_list['list']);
            } else {
                get_down_oss_template($oss_bonusadv_list);
            }

            /* 下载目录下文件 */
            $oss_content_list = app(DscRepository::class)->getOssListFile(['prefix' => DATA_DIR . '/home_templates/' . $GLOBALS['_CFG']['template'] . '/' . $suffix . '/images/content/']);

            if (!is_array($oss_list)) {
                $oss_content_list = dsc_decode($oss_content_list, true);
                get_down_oss_template($oss_content_list['list']);
            } else {
                get_down_oss_template($oss_content_list);
            }


            write_static_cache($suffix, [1]);
        }
    }
}

/**
 * 专题可视化
 * 下载OSS模板文件
 */
function get_down_topictemplates($topic_id = 0, $seller_id = 0)
{

    /* 存入OSS start */
    if ($GLOBALS['_CFG']['open_oss'] && $GLOBALS['_CFG']['server_model']) {
        if (!file_exists(storage_public('data/sc_file/topic/topic_' . $seller_id . "/" . "topic_" . $topic_id . ".php"))) {
            /* 下载目录下文件 */
            $oss_list = app(DscRepository::class)->getOssListFile(['prefix' => "data/topic/topic_" . $seller_id . "/" . "topic_" . $topic_id . '/']);

            if (!is_array($oss_list)) {
                $oss_list = dsc_decode($oss_list, true);
                get_down_oss_template($oss_list['list']);
            } else {
                get_down_oss_template($oss_list);
            }

            /* 下载目录下文件 */
            $oss_images_list = app(DscRepository::class)->getOssListFile(['prefix' => "data/topic/topic_" . $seller_id . "/" . "topic_" . $topic_id . '/images/']);

            if (!is_array($oss_images_list)) {
                $oss_images_list = dsc_decode($oss_images_list, true);
                get_down_oss_template($oss_images_list['list']);
            } else {
                get_down_oss_template($oss_images_list);
            }

            /* 下载目录下文件 */
            $oss_content_list = app(DscRepository::class)->getOssListFile(['prefix' => "data/topic/topic_" . $seller_id . "/" . "topic_" . $topic_id . '/images/content/']);

            if (!is_array($oss_content_list)) {
                $oss_content_list = dsc_decode($oss_content_list, true);
                get_down_oss_template($oss_content_list['list']);
            } else {
                get_down_oss_template($oss_content_list);
            }

            write_static_cache("topic_" . $topic_id, [1]);
        }
    }
    /* 存入OSS end */
}

/**
 * 店铺可视化
 * 下载OSS模板文件
 */
function get_down_sellertemplates($merchant_id = 0, $tem = '')
{

    /* 存入OSS start */
    if ($GLOBALS['_CFG']['open_oss'] && $GLOBALS['_CFG']['server_model'] && !empty($tem)) {
        if (!file_exists(storage_public('data/sc_file/sellertemplates/seller_tem_' . $merchant_id . '/' . $tem . ".php"))) {
            /* 下载目录下文件 */
            $oss_list = app(DscRepository::class)->getOssListFile(['prefix' => 'data/seller_templates/seller_tem_' . $merchant_id . '/' . $tem . "/"]);

            if (!is_array($oss_list)) {
                $oss_list = dsc_decode($oss_list, true);
                get_down_oss_template($oss_list['list']);
            } else {
                get_down_oss_template($oss_list);
            }

            /* 下载目录下文件 */
            $oss_css_list = app(DscRepository::class)->getOssListFile(['prefix' => 'data/seller_templates/seller_tem_' . $merchant_id . '/' . $tem . '/css/']);

            if (!is_array($oss_css_list)) {
                $oss_css_list = dsc_decode($oss_css_list, true);
                get_down_oss_template($oss_css_list['list']);
            } else {
                get_down_oss_template($oss_css_list);
            }

            /* 下载目录下文件 */
            $oss_images_list = app(DscRepository::class)->getOssListFile(['prefix' => 'data/seller_templates/seller_tem_' . $merchant_id . '/' . $tem . '/images/']);

            if (!is_array($oss_images_list)) {
                $oss_images_list = dsc_decode($oss_images_list, true);
                get_down_oss_template($oss_images_list['list']);
            } else {
                get_down_oss_template($oss_images_list);
            }

            /* 下载目录下文件 */
            $oss_head_list = app(DscRepository::class)->getOssListFile(['prefix' => 'data/seller_templates/seller_tem_' . $merchant_id . '/' . $tem . '/images/head/']);

            if (!is_array($oss_head_list)) {
                $oss_head_list = dsc_decode($oss_head_list, true);
                get_down_oss_template($oss_head_list['list']);
            } else {
                get_down_oss_template($oss_head_list);
            }

            /* 下载目录下文件 */
            $oss_content_list = app(DscRepository::class)->getOssListFile(['prefix' => 'data/seller_templates/seller_tem_' . $merchant_id . '/' . $tem . '/images/content/']);

            if (!is_array($oss_content_list)) {
                $oss_content_list = dsc_decode($oss_content_list, true);
                get_down_oss_template($oss_content_list['list']);
            } else {
                get_down_oss_template($oss_content_list);
            }

            write_static_cache($tem, [1]);
        }
    }
    /* 存入OSS end */
}

//获取文章列表
function getcat_atr($where = [])
{
    $children = [];
    if (isset($where['cat_id'])) {
        $ArticleCatRep = app(ArticleCatService::class);
        $children = $ArticleCatRep->getCatListChildren($where['cat_id']);
    }

    $row = Article::where('is_open', 1)
        ->where('article_type', 0);

    if ($children) {
        $row = $row->whereIn('cat_id', $children);
    }

    if (isset($where['article_id']) && $where['article_id']) {
        $where['article_id'] = app(BaseRepository::class)->getExplode($where['article_id']);
        $row = $row->whereIn('article_id', $where['article_id']);
    }

    $res = $record_count = $row;

    $filter['record_count'] = $record_count->count();

    $filter = page_and_size($filter);

    $res = $res->orderBy('article_type', 'desc')
        ->orderBy('add_time', 'desc');

    if ($filter['start'] > 0) {
        $res = $res->skip($filter['start']);
    }

    if ($filter['page_size'] > 0) {
        $res = $res->take($filter['page_size']);
    }

    $res = app(BaseRepository::class)->getToArrayGet($res);

    $filter['page_arr'] = seller_page($filter, $filter['page']);
    return ['list' => $res, 'filter' => $filter];
}

//获取首页模板列表
function get_home_templates()
{
    $adminru = get_admin_ru_id();

    $result = get_filter();
    if ($result === false) {

        /* 初始化分页参数 */
        $filter = [];

        $row = HomeTemplates::whereRaw(1);

        if ($adminru['rs_id'] > 0) {
            $row = $row->where('rs_id', $adminru['rs_id']);
        }

        $row = $row->where('theme', $GLOBALS['_CFG']['template']);

        $res = $record_count = $row;

        /* 查询记录总数，计算分页数 */
        $filter['record_count'] = $record_count->count();

        $filter = page_and_size($filter);

        /* 查询记录 */

        if ($filter['start'] > 0) {
            $res = $res->skip($filter['start']);
        }

        if ($filter['page_size'] > 0) {
            $res = $res->take($filter['page_size']);
        }

        $res = app(BaseRepository::class)->getToArrayGet($res);
    } else {
        $filter = $result['filter'];

        $res = [];
    }

    $default_tem = '';
    $arr = [];
    if ($res) {
        foreach ($res as $rows) {
            $seller_template_info = [
                'screenshot' => '',
                'template' => '',
                'name' => '',
                'uri' => '',
                'desc' => '',
                'version' => '',
                'author' => '',
                'author_uri' => ''
            ];

            //获取模板信息
            if ($rows['code']) {
                $seller_template_info = get_seller_template_info($rows['code'], 0, $GLOBALS['_CFG']['template']);
            }

            //赋值
            $rows['screenshot'] = $seller_template_info['screenshot'];
            $rows['template'] = isset($seller_template_info['template']) ? $seller_template_info['template'] : '';
            $rows['name'] = $seller_template_info['name'];
            $rows['uri'] = $seller_template_info['uri'];
            $rows['desc'] = $seller_template_info['desc'];
            $rows['version'] = $seller_template_info['version'];
            $rows['author'] = $seller_template_info['author'];
            $rows['author_uri'] = $seller_template_info['author_uri'];
            $rows['code'] = $rows['code'];

            if ($rows['is_enable'] == 1 && $rows['rs_id'] == $adminru['rs_id']) {
                $default_tem = $rows['code'];
            }

            $rows['rs_name'] = isset($rows['get_region_store']) ? $rows['get_region_store']['rs_name'] : '';

            $arr[] = $rows;
        }
    }

    return ['list' => $arr, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count'], 'default_tem' => $default_tem];
}
