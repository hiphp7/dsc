<?php

use App\Repositories\Common\DscRepository;
use App\Services\Category\CategoryService;

/**
 * 静态资源
 * @param string $type 资源类型
 * @param string $module 资源所属模块
 * @param int $mode
 * @param string $path
 * @return string
 */
function global_assets($type = 'css', $module = 'app', $mode = 1, $path = '')
{
    $assets = config('assets');
    $gulps = ['dist' => '/'];

    $public_path = isset($path) ? asset('/assets/' . $path) : asset('/');

    if (config('app.debug') || $mode) {
        $resources = './';
        $paths = [];
        foreach ($assets as $key => $item) {
            foreach ($item as $vo) {
                if (substr($vo, -3) == '.js') {
                    $paths[$key]['js'][] = '<script src="' . $public_path . '/' . $vo . '?v=' . time() . '"></script>';
                    $gulps[$key]['js'][] = $resources . $vo;
                } elseif (substr($vo, -4) == '.css') {
                    $paths[$key]['css'][] = '<link href="' . $public_path . '/' . $vo . '?v=' . time() . '" rel="stylesheet" type="text/css" />';
                    $gulps[$key]['css'][] = $resources . $vo;
                }
            }
        }
        // file_put_contents(base_path('storage/webpack.config.js'), 'module.exports = ' . json_encode($gulps));
    } else {
        $paths[$module] = [
            'css' => ['<link href="' . asset('static/css/' . $module . '.min.css') . '?v=' . VERSION . '" rel="stylesheet" type="text/css" />'],
            'js' => ['<script src="' . asset('static/js/' . $module . '.min.js') . '?v=' . VERSION . '"></script>']
        ];
    }

    return isset($paths[$module][$type]) ? implode("\n", $paths[$module][$type]) . "\n" : '';
}

/**
 * 生成可视化编辑器
 *
 * @param string $input_name 输入框名称
 * @param string $input_value 输入框值
 * @param int $width 编辑器宽度
 * @param int $height 编辑器高度
 *
 * @return string
 */
function create_editor($input_name= '', $input_value = '', $width = 700, $height = 360)
{
    static $ueditor_created = false;
    $editor = '';
    if (!$ueditor_created) {
        $ueditor_created = true;
        $editor .= '<script type="text/javascript" src="' . asset('vendor/ueditor/ueditor.config.js') . '"></script>';
        $editor .= '<script type="text/javascript" src="' . asset('vendor/ueditor/ueditor.all.min.js') . '"></script>';
        $editor .= '<script>window.UEDITOR_CONFIG.serverUrl = "' . config('ueditor.route.name') . '";</script>';
    }

    $px = 'px';

    $editor .= '<script id="ue_' . $input_name . '" name="' . $input_name . '" type="text/plain" style="width:' . $width . $px . ';height:' . $height . $px . ';">' . htmlspecialchars_decode($input_value) . '</script>';
    $editor .= '<script type="text/javascript">

    var config = {toolbars: [[
      "fullscreen", "source", "|", "undo", "redo", "|",
      "bold", "italic", "underline", "fontborder", "strikethrough", "superscript", "subscript", "blockquote",  "|", "forecolor", "backcolor", "insertorderedlist", "insertunorderedlist", "selectall", "cleardoc", "|",
      "rowspacingtop", "rowspacingbottom", "lineheight", "|", "fontfamily", "fontsize", "|",
      "directionalityltr", "directionalityrtl", "indent", "|",
      "justifyleft", "justifycenter", "justifyright", "justifyjustify", "|", "touppercase", "tolowercase", "|",
      "link", "unlink", "anchor", "|", "imagenone", "imageleft", "imageright", "imagecenter", "|",
      "simpleupload", "insertimage", "insertvideo", "attachment", "map", "drafts"
    ]],
    initialFrameWidth : "' . $width . '",
    initialFrameHeight : "' . $height . '",
    };

    var ue_' . $input_name . ' = UE.getEditor("ue_' . $input_name . '", config);

    ue_' . $input_name . '.ready(function() {
        ue_' . $input_name . '.execCommand("serverparam", "_token", "' . csrf_token() . '"); // 设置 CSRF token.
    });

    </script>';

    return $editor;
}


//设置默认筛选 分类。品牌列表
function set_default_filter_new($goods_id = 0, $cat_id = 0)
{
    $filter = [
        'filter_category_navigation' => '',
        'filter_category_list' => '',
        'filter_brand_list' => '',
    ];
    //分类导航
    if ($cat_id > 0) {
        $parent_cat_list = get_select_category($cat_id, 1, true);
        $filter['filter_category_navigation'] = get_array_category_info($parent_cat_list);
    }

    $filter['filter_category_list'] = get_category_list($cat_id);//分类列表
    $filter['filter_brand_list'] = search_brand_list($goods_id);//品牌列表

    return $filter;
}

/**
 * 获得指定分类下所有底层分类的ID
 *
 * @access  public
 * @param integer $cat 指定的分类ID
 * @return  string
 */
function get_children_new($cat = 0, $type = 0, $child_three = 0)
{
    /**
     * 当前分类下的所有子分类
     * 返回一维数组
     */
    $cat_keys = app(CategoryService::class)->getCatListChildren($cat);

    if ($type != 2) {
        if ($child_three == 1) {
            if ($cat) {
                return $cat;
            }
        } else {
            $cat = array_unique(array_merge([$cat], $cat_keys));

            return $cat;
        }
    } else {
        $cat_keys = !empty($cat_keys) ? implode(",", $cat_keys) : '';
        $cat_keys = app(DscRepository::class)->delStrComma($cat_keys);

        return $cat_keys;
    }
}
