<?php

namespace App\Modules\Admin\Controllers;


use App\Libraries\Pinyin;
use App\Models\Region;
use App\Repositories\Common\BaseRepository;
use Illuminate\Support\Facades\Artisan;

/**
 * 地区列表管理文件
 */
class AreaManageController extends InitController
{
    protected $baseRepository;

    public function __construct(
        BaseRepository $baseRepository
    )
    {
        $this->baseRepository = $baseRepository;
    }

    public function index()
    {

        /* act操作项的初始化 */
        if (empty($_REQUEST['act'])) {
            $_REQUEST['act'] = 'list';
        } else {
            $_REQUEST['act'] = trim($_REQUEST['act']);
        }

        /*------------------------------------------------------ */
        //-- 列出某地区下的所有地区列表
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'list') {
            admin_priv('area_list');

            $this->smarty->assign('menu_select', ['action' => '01_system', 'current' => '05_area_list']);

            /* 取得参数：上级地区id */
            $region_id = empty($_REQUEST['pid']) ? 0 : intval($_REQUEST['pid']);
            $this->smarty->assign('parent_id', $region_id);

            /* 取得列表显示的地区的类型 */
            if ($region_id == 0) {
                $region_type = 0;
            } else {
                $region_type = Region::where('region_id', $region_id)->value('region_type');
                $region_type = $region_type ? $region_type : 0;

                $region_type = $region_type + 1;
            }
            $this->smarty->assign('region_type', $region_type);

            /* 获取地区列表 */
            $region_arr = area_list($region_id);
            $this->smarty->assign('region_arr', $region_arr);
            $area_top = '-';
            $area = '';
            /* 当前的地区名称 */
            if ($region_id > 0) {
                $area_name = Region::where('region_id', $region_id)->value('region_name');
                $area_name = $area_name ? $area_name : '';
                $area_top = $area_name;
                if ($region_arr) {
                    $area = $region_arr[0]['type'];
                }
            } else {
                $area = $GLOBALS['_LANG']['country'];
            }
            $this->smarty->assign('area_top', $area_top);
            $this->smarty->assign('area_here', $area);

            /* 返回上一级的链接 */
            if ($region_id > 0) {
                $parent_id = Region::where('region_id', $region_id)->value('parent_id');
                $parent_id = $parent_id ? $parent_id : 0;

                $action_link = ['text' => $GLOBALS['_LANG']['back_page'], 'href' => 'area_manage.php?act=list&&pid=' . $parent_id, 'type' => 1];
            } else {
                $action_link = ['text' => $GLOBALS['_LANG']['create_region_initial'], 'href' => 'area_manage.php?act=create_region_initial'];
            }
            $this->smarty->assign('action_link', $action_link);

            /* 赋值模板显示 */
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['05_area_list']);
            $this->smarty->assign('full_page', 1);


            return $this->smarty->display('area_list.dwt');
        }

        /*------------------------------------------------------ */
        //-- 恢复默认地区 by wu
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'restore_region') {
            admin_priv('area_list');

            $link[] = ['text' => $GLOBALS['_LANG']['go_back'], 'href' => 'area_manage.php?act=list'];

            //清空数据表
            Region::truncate();

            Artisan::call('db:seed', [
                '--class' => 'RegionSeeder',
            ]);

            return sys_msg($GLOBALS['_LANG']['restore_success'], 0, $link);
        }

        /*------------------------------------------------------ */
        //-- 生成地区首字母 start
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'create_region_initial') {
            admin_priv('area_list');

            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['create_region_initial']);

            $region_list = get_city_region();
            $record_count = count($region_list);

            $this->smarty->assign('record_count', $record_count);
            $this->smarty->assign('page', 1);

            if ($this->baseRepository->getDiskForeverExists('forever_pin_regions')) {
                $this->baseRepository->getDiskForeverDelete('forever_pin_regions');
            }

            return $this->smarty->display('area_initial.dwt');
        }

        /*------------------------------------------------------ */
        //-- 生成地区首字母 end
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'ajax_region_initial') {

            $page = !empty($_REQUEST['page']) ? intval($_REQUEST['page']) : 1;
            $page_size = isset($_REQUEST['page_size']) ? intval($_REQUEST['page_size']) : 1;

            $region_list = get_city_region();
            $region_list = $this->dsc->page_array($page_size, $page, $region_list);

            $pin = new Pinyin();
            $list = [];
            //26个英文字母数组
            $letters = range('A', 'Z');

            if ($region_list && $region_list['list']) {
                foreach ($region_list['list'] as $key => $region) {
                    if ($region) {
                        foreach ($letters as $val) {
                            if (strtolower(substr($region['region_name'], 0, 1)) == strtolower($val)) {
                                $region_list[$key] = $region;
                                $region_list[$key]['initial'] = $val;
                            } else {

                                if (strtolower($val) == substr($pin->Pinyin($region['region_name'], EC_CHARSET), 0, 1)) {
                                    $region_list[$key] = $region;
                                    $region_list[$key]['initial'] = $val;
                                }
                            }
                        }
                    }

                    $list = [
                        'region_id' => $region['region_id'],
                        'parent_id' => $region['parent_id'],
                        'region_name' => $region['region_name'],
                        'is_has' => $region['is_has'],
                        'initial' => $region_list[$key]['initial']
                    ];
                }
            }

            $result['list'] = $list;

            if ($result['list']) {
                $pin_regions = $this->baseRepository->getDiskForeverData('forever_pin_regions');
                if ($pin_regions === false) {
                    $this->baseRepository->setDiskForever('forever_pin_regions', [$result['list']]);
                } else {
                    array_push($pin_regions, $result['list']);
                    $this->baseRepository->setDiskForever('forever_pin_regions', $pin_regions);
                }
            }

            if ($region_list) {
                $result['page'] = $region_list['filter']['page'] + 1;
                $result['page_size'] = $region_list['filter']['page_size'];
                $result['record_count'] = $region_list['filter']['record_count'];
                $result['page_count'] = $region_list['filter']['page_count'];

                $result['is_stop'] = 1;
                if ($page > $region_list['filter']['page_count']) {
                    $result['is_stop'] = 0;
                    $regions = get_pin_regions();

                    if ($regions) {
                        if ($this->baseRepository->getDiskForeverExists('forever_pin_regions')) {
                            $this->baseRepository->getDiskForeverDelete('forever_pin_regions');
                        }

                        $this->baseRepository->setDiskForever('forever_pin_regions', $regions);
                    }
                } else {
                    $result['filter_page'] = $region_list['filter']['page'];
                }
            }

            return response()->json($result);
        }
        /*------------------------------------------------------ */
        //-- 添加新的地区
        /*------------------------------------------------------ */

        elseif ($_REQUEST['act'] == 'add_area') {
            $check_auth = check_authz_json('area_list');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $parent_id = intval($_POST['parent_id']);
            $region_name = json_str_iconv(trim($_POST['region_name']));
            $region_type = intval($_POST['region_type']);
            $region_id = isset($region_id) ? $region_id : 0;

            /* 获取地区列表 */
            $region_arr = area_list($region_id);
            $this->smarty->assign('region_arr', $region_arr);
            $area_top = '-';
            /* 当前的地区名称 */
            if ($region_id > 0) {
                $area_name = Region::where('region_id', $region_id)->value('region_name');
                $area_name = $area_name ? $area_name : '';
                $area_top = $area_name;
                if ($region_arr) {
                    $area = $region_arr[0]['type'];
                }
            } else {
                $area = $GLOBALS['_LANG']['country'];
            }
            $this->smarty->assign('area_top', $area_top);
            $this->smarty->assign('area_here', $area);

            if (empty($region_name)) {
                return make_json_error($GLOBALS['_LANG']['region_name_empty']);
            }

            $is_only = Region::where('region_name', $region_name)
                ->where('parent_id', $parent_id)
                ->count();

            /* 查看区域是否重复 */
            if ($is_only > 0) {
                return make_json_error($GLOBALS['_LANG']['region_name_exist']);
            }

            $other = [
                'parent_id' => $parent_id,
                'region_name' => $region_name,
                'region_type' => $region_type
            ];
            $region_id = Region::insertGetId($other);

            if ($region_id) {
                admin_log($region_name, 'add', 'area');

                /* 获取地区列表 */
                $region_arr = area_list($parent_id);

                if ($region_arr) {
                    foreach ($region_arr as $k => $v) {

                        $parent_name = Region::where('region_id', $v['parent_id'])->value('region_name');
                        $parent_name = $parent_name ? $parent_name : '';

                        $region_arr[$k]['parent_name'] = $parent_name;
                    }
                }

                $this->smarty->assign('region_arr', $region_arr);
                $this->smarty->assign('region_type', $region_type);

                return make_json_result($this->smarty->fetch('library/area_list.lbi'));
            } else {
                return make_json_error($GLOBALS['_LANG']['add_area_error']);
            }
        }

        /*------------------------------------------------------ */
        //-- 编辑区域名称
        /*------------------------------------------------------ */

        elseif ($_REQUEST['act'] == 'edit_area_name') {
            $check_auth = check_authz_json('area_list');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $id = intval($_POST['id']);
            $region_name = json_str_iconv(trim($_POST['val']));

            if (empty($region_name)) {
                return make_json_error($GLOBALS['_LANG']['region_name_empty']);
            }

            /* 查看区域是否重复 */
            $parent_id = Region::where('region_id', $id)->value('parent_id');
            $parent_id = $parent_id ? $parent_id : 0;
            $is_only = Region::where('region_name', $region_name)
                ->where('parent_id', $parent_id)
                ->where('region_id', '<>', $id)
                ->count();

            if ($is_only > 0) {
                return make_json_error($GLOBALS['_LANG']['region_name_exist']);
            }

            $res = Region::where('region_id', $id)->update([
                'region_name' => $region_name
            ]);

            if ($res) {
                admin_log($region_name, 'edit', 'area');
                return make_json_result(stripslashes($region_name));
            } else {
                return make_json_error($this->db->error());
            }
        }

        /*------------------------------------------------------ */
        //-- 删除区域
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'drop_area') {
            $check_auth = check_authz_json('area_list');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $id = intval($_REQUEST['id']);

            $region = Region::where('region_id', $id);

            $region = $this->baseRepository->getToArrayFirst($region);

            $region_arr = area_list($region['parent_id']);

            if ($region_arr) {
                foreach ($region_arr as $k => $v) {

                    $parent_name = Region::where('region_id', $region['parent_id'])->value('region_name');
                    $parent_name = $parent_name ? $parent_name : '';

                    $region_arr[$k]['parent_name'] = $parent_name;
                }
            }

            $this->smarty->assign('region_arr', $region_arr);
            $region_type = $region['region_type'];
            $delete_region[] = $id;
            $new_region_id = $id;
            if ($region_type < 6) {
                for ($i = 1; $i < 6 - $region_type; $i++) {
                    $new_region_id = $this->new_region_id($new_region_id);
                    if (count($new_region_id)) {
                        $delete_region = array_merge($delete_region, $new_region_id);
                    } else {
                        continue;
                    }
                }
            }

            $res = Region::whereIn('region_id', $delete_region)->delete();

            if ($res) {
                admin_log(addslashes($region['region_name']), 'remove', 'area');

                /* 获取地区列表 */
                $region_arr = area_list($region['parent_id']);

                if ($region_arr) {
                    foreach ($region_arr as $k => $v) {
                        $parent_name = Region::where('region_id', $region['parent_id']);
                        $parent_name = $this->baseRepository->getToArrayFirst($parent_name);
                        $region_arr[$k]['parent_name'] = $parent_name;
                    }
                }

                $this->smarty->assign('region_arr', $region_arr);
                $this->smarty->assign('region_type', $region['region_type']);

                return make_json_result($this->smarty->fetch('library/area_list.lbi'));

            } else {
                return make_json_error($this->db->error());
            }
        }
    }


    private function new_region_id($region_id)
    {
        $regions_id = [];
        if (empty($region_id)) {
            return $regions_id;
        }

        $region_id = $this->baseRepository->getExplode($region_id);

        $result = Region::whereIn('parent_id', $region_id);
        $result = $this->baseRepository->getToArrayGet($result);

        if ($result) {
            foreach ($result as $val) {
                $regions_id[] = $val['region_id'];
            }
        }

        return $regions_id;
    }
}
