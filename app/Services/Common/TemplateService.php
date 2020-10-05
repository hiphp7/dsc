<?php

namespace App\Services\Common;

use App\Models\Template;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\DscRepository;

/**
 * 模板调用
 * Class Template
 * @package App\Services
 */
class TemplateService
{
    protected $baseRepository;
    protected $dscRepository;
    protected $config;

    public function __construct(
        BaseRepository $baseRepository,
        DscRepository $dscRepository
    )
    {
        $this->baseRepository = $baseRepository;
        $this->dscRepository = $dscRepository;
        $this->config = $this->dscRepository->dscConfig();
    }

    /*
     * 获取楼层内容
     *
     * @param String type: index-首页，category-分类
     * @param int id: 可以是cat_id或其他
     * @return array
     */
    public function getFloorData($type = '', $id = 0)
    {
        $data = [];

        if ($type == 'index') {
            $res = Template::where('filename', 'index')
                ->where('type', 1)
                ->where('theme', $this->config['template'] . " OR 1 = 1")
                ->where('remarks', '')
                ->whereHas('getFloorCategory', function ($query) {
                    $query->where('is_show', 1);
                });

            $res = $res->with(['getFloorCategory']);

            $res = $res->orderBy('sort_order');

            $res = $this->baseRepository->getToArrayGet($res);

            if ($res) {
                foreach ($res as $key => $val) {
                    $cat = $val['get_floor_category'];

                    $arr['id'] = $cat['cat_id'];
                    $arr['name'] = $cat['cat_alias_name'];
                    $data[] = $arr;
                }
            }
        }

        return $data;
    }

    /**
     * 取得某模板某库设置的数量
     *
     * @param string $library 库名，如recommend_best
     * @param null $template 模板名，如index
     * @return int
     */
    public function getLibraryNumber($library = '', $template = null)
    {
        if (empty($template)) {
            $template = basename(PHP_SELF);
            $template = substr($template, 0, strrpos($template, '.'));
        }
        $template = addslashes($template);

        static $lib_list = [];

        /* 如果没有该模板的信息，取得该模板的信息 */
        if (!isset($lib_list[$template])) {
            $lib_list[$template] = [];

            $res = Template::where('theme', $this->config['template'])->where('filename', $template)->where('remarks', '');
            $res = $this->baseRepository->getToArrayGet($res);

            if ($res) {
                foreach ($res as $row) {
                    $lib = basename(strtolower(substr($row['library'], 0, strpos($row['library'], '.'))));
                    $lib_list[$template][$lib] = $row['number'];
                }
            }
        }

        if (isset($lib_list[$template][$library])) {
            $num = intval($lib_list[$template][$library]);
        } else {
            /* 模板设置文件查找默认值 */
            static $static_page_libs = null;
            $lib = '/library/' . $library . '.lbi';

            $num = isset($static_page_libs[$template][$lib]) ? $static_page_libs[$template][$lib] : 3;
        }

        return $num;
    }
}
