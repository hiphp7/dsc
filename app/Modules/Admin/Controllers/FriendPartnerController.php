<?php

namespace App\Modules\Admin\Controllers;

use App\Libraries\Image;
use App\Models\PartnerList;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\DscRepository;
use App\Services\Friend\FriendPartnerManageService;

/**
 * 合作伙伴管理
 */
class FriendPartnerController extends InitController
{
    protected $baseRepository;
    protected $friendPartnerManageService;
    protected $dscRepository;

    public function __construct(
        BaseRepository $baseRepository,
        FriendPartnerManageService $friendPartnerManageService,
        DscRepository $dscRepository
    )
    {
        $this->baseRepository = $baseRepository;
        $this->friendPartnerManageService = $friendPartnerManageService;
        $this->dscRepository = $dscRepository;
    }

    public function index()
    {
        $image = new Image(['bgcolor' => $GLOBALS['_CFG']['bgcolor']]);

        /* act操作项的初始化 */
        if (empty($_REQUEST['act'])) {
            $_REQUEST['act'] = 'list';
        } else {
            $_REQUEST['act'] = trim($_REQUEST['act']);
        }

        /*------------------------------------------------------ */
        //-- 合作伙伴列表页面
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'list') {
            /* 模板赋值 */
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['list_link']);
            $this->smarty->assign('action_link', ['text' => $GLOBALS['_LANG']['add_link'], 'href' => 'friend_partner.php?act=add']);
            $this->smarty->assign('full_page', 1);

            /* 获取合作伙伴数据 */
            $links_list = $this->friendPartnerManageService->getLinksList();

            $this->smarty->assign('links_list', $links_list['list']);
            $this->smarty->assign('filter', $links_list['filter']);
            $this->smarty->assign('record_count', $links_list['record_count']);
            $this->smarty->assign('page_count', $links_list['page_count']);

            $sort_flag = sort_flag($links_list['filter']);
            $this->smarty->assign($sort_flag['tag'], $sort_flag['img']);


            return $this->smarty->display('partner_list.dwt');
        }

        /*------------------------------------------------------ */
        //-- 排序、分页、查询
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'query') {
            /* 获取合作伙伴数据 */
            $links_list = $this->friendPartnerManageService->getLinksList();

            $this->smarty->assign('links_list', $links_list['list']);
            $this->smarty->assign('filter', $links_list['filter']);
            $this->smarty->assign('record_count', $links_list['record_count']);
            $this->smarty->assign('page_count', $links_list['page_count']);

            $sort_flag = sort_flag($links_list['filter']);
            $this->smarty->assign($sort_flag['tag'], $sort_flag['img']);

            return make_json_result(
                $this->smarty->fetch('partner_list.dwt'),
                '',
                ['filter' => $links_list['filter'], 'page_count' => $links_list['page_count']]
            );
        }

        /*------------------------------------------------------ */
        //-- 添加新链接页面
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'add') {
            admin_priv('partnerlink');

            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['add_link']);
            $this->smarty->assign('action_link', ['href' => 'friend_partner.php?act=list', 'text' => $GLOBALS['_LANG']['list_link']]);
            $this->smarty->assign('action', 'add');
            $this->smarty->assign('form_act', 'insert');


            return $this->smarty->display('partner_info.dwt');
        }

        /*------------------------------------------------------ */
        //-- 处理添加的链接
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'insert') {
            /* 变量初始化 */
            $link_logo = '';
            $show_order = (!empty($_POST['show_order'])) ? intval($_POST['show_order']) : 0;
            $link_name = (!empty($_POST['link_name'])) ? $this->dscRepository->subStr(trim($_POST['link_name']), 250, false) : '';

            /* 查看链接名称是否有重复 */
            $res = PartnerList::where('link_name', $link_name)->count();
            if ($res < 1) {
                /* 处理上传的LOGO图片 */
                if ((isset($_FILES['link_img']['error']) && $_FILES['link_img']['error'] == 0) || (!isset($_FILES['link_img']['error']) && isset($_FILES['link_img']['tmp_name']) && $_FILES['link_img']['tmp_name'] != 'none')) {
                    $link_logo = $image->upload_image($_FILES['link_img'], 'afficheimg');
                }

                /* 使用远程的LOGO图片 */
                if (!empty($_POST['url_logo'])) {
                    if (strpos($_POST['url_logo'], 'http://') === false && strpos($_POST['url_logo'], 'https://') === false) {
                        $link_logo = 'http://' . trim($_POST['url_logo']);
                    } else {
                        $link_logo = trim($_POST['url_logo']);
                    }
                }

                /* 如果链接LOGO为空, LOGO为链接的名称 */
                if (((isset($_FILES['upfile_flash']['error']) && $_FILES['upfile_flash']['error'] > 0) || (!isset($_FILES['upfile_flash']['error']) && isset($_FILES['upfile_flash']['tmp_name']) && $_FILES['upfile_flash']['tmp_name'] == 'none')) && empty($_POST['url_logo'])) {
                    $link_logo = '';
                }

                /* 如果合作伙伴的链接地址没有http://，补上 */
                if (strpos($_POST['link_url'], 'http://') === false && strpos($_POST['link_url'], 'https://') === false) {
                    $link_url = 'http://' . trim($_POST['link_url']);
                } else {
                    $link_url = trim($_POST['link_url']);
                }

                $this->dscRepository->getOssAddFile([$link_logo]);

                /* 插入数据 */

                $data = ['link_name' => $link_name,
                    'link_url' => $link_url,
                    'link_logo' => $link_logo,
                    'show_order' => $show_order,
                ];
                PartnerList::insert($data);
                /* 记录管理员操作 */
                admin_log($_POST['link_name'], 'add', 'partnerlink');

                /* 清除缓存 */
                clear_cache_files();

                /* 提示信息 */
                $link[0]['text'] = $GLOBALS['_LANG']['continue_add'];
                $link[0]['href'] = 'friend_partner.php?act=add';

                $link[1]['text'] = $GLOBALS['_LANG']['back_list'];
                $link[1]['href'] = 'friend_partner.php?act=list';

                return sys_msg($GLOBALS['_LANG']['add'] . "&nbsp;" . stripcslashes($_POST['link_name']) . " " . $GLOBALS['_LANG']['attradd_succed'], 0, $link);
            } else {
                $link[] = ['text' => $GLOBALS['_LANG']['go_back'], 'href' => 'javascript:history.back(-1)'];
                return sys_msg($GLOBALS['_LANG']['link_name_exist'], 0, $link);
            }
        }

        /*------------------------------------------------------ */
        //-- 合作伙伴编辑页面
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'edit') {
            admin_priv('partnerlink');

            $id = isset($_REQUEST['id']) && !empty($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
            /* 取得合作伙伴数据 */
            $res = PartnerList::where('link_id', $id);
            $link_arr = $this->baseRepository->getToArrayFirst($res);

            /* 标记为图片链接还是文字链接 */
            if (!empty($link_arr['link_logo'])) {
                $type = 'img';
                $link_logo = get_image_path($link_arr['link_logo']);
            } else {
                $type = 'chara';
                $link_logo = '';
            }

            $link_arr['link_name'] = $this->dscRepository->subStr($link_arr['link_name'], 250, false); // 截取字符串为250个字符避免出现非法字符的情况

            /* 模板赋值 */
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['edit_link']);
            $this->smarty->assign('action_link', ['href' => 'friend_partner.php?act=list&' . list_link_postfix(), 'text' => $GLOBALS['_LANG']['list_link']]);
            $this->smarty->assign('form_act', 'update');
            $this->smarty->assign('action', 'edit');

            $this->smarty->assign('type', $type);
            $this->smarty->assign('link_logo', $link_logo);
            $this->smarty->assign('link_arr', $link_arr);


            return $this->smarty->display('partner_info.dwt');
        }

        /*------------------------------------------------------ */
        //-- 编辑链接的处理页面
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'update') {
            /* 变量初始化 */
            $id = (!empty($_REQUEST['id'])) ? intval($_REQUEST['id']) : 0;
            $show_order = (!empty($_POST['show_order'])) ? intval($_POST['show_order']) : 0;
            $link_name = (!empty($_POST['link_name'])) ? trim($_POST['link_name']) : '';

            /* 如果有图片LOGO要上传 */
            if ((isset($_FILES['link_img']['error']) && $_FILES['link_img']['error'] == 0) || (!isset($_FILES['link_img']['error']) && isset($_FILES['link_img']['tmp_name']) && $_FILES['link_img']['tmp_name'] != 'none')) {
                $linklogo_img = $image->upload_image($_FILES['link_img'], 'afficheimg');
                $data['link_logo'] = "'{$linklogo_img}'";
            } elseif (!empty($_POST['url_logo'])) {
                $data['link_logo'] = $_POST['url_logo'];
                $linklogo_img = $_POST['url_logo'];
            } else {
                /* 如果是文字链接, LOGO为链接的名称 */
                $data['link_logo'] = '';
                $linklogo_img = '';
            }

            //如果要修改链接图片, 删除原来的图片
            if (!empty($img_up_info)) {
                //获取链子LOGO,并删除
                $old_logo = PartnerList::where('link_id', $id)->value('link_logo');
                if ((strpos($old_logo, 'http://') === false) && (strpos($old_logo, 'https://') === false)) {
                    $img_name = basename($old_logo);
                    @unlink(storage_public(DATA_DIR . '/afficheimg/' . $img_name));
                    $this->dscRepository->getOssDelFile([DATA_DIR . '/afficheimg/' . $img_name]);
                }
            }

            /* 如果合作伙伴的链接地址没有http://，补上 */
            if (strpos($_POST['link_url'], 'http://') === false && strpos($_POST['link_url'], 'https://') === false) {
                $link_url = 'http://' . trim($_POST['link_url']);
            } else {
                $link_url = trim($_POST['link_url']);
            }

            $this->dscRepository->getOssAddFile([$linklogo_img]);

            /* 更新信息 */
            $data['link_name'] = $link_name;
            $data['link_url'] = $link_url;
            $data['show_order'] = $show_order;
            PartnerList::where('link_id', $id)->update($data);

            /* 记录管理员操作 */
            admin_log($_POST['link_name'], 'edit', 'partnerlink');

            /* 清除缓存 */
            clear_cache_files();

            /* 提示信息 */
            $link[0]['text'] = $GLOBALS['_LANG']['back_list'];
            $link[0]['href'] = 'friend_partner.php?act=list&' . list_link_postfix();

            return sys_msg($GLOBALS['_LANG']['edit'] . "&nbsp;" . stripcslashes($_POST['link_name']) . "&nbsp;" . $GLOBALS['_LANG']['attradd_succed'], 0, $link);
        }

        /*------------------------------------------------------ */
        //-- 删除图片处理
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'delPic') {
            $check_auth = check_authz_json('partnerlink');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $id = intval($_GET['link_id']);

            $result = ['error' => 0, 'content' => ''];
            /* 获取链子LOGO,并删除 */
            $link_logo = PartnerList::where('link_id', $id)->value('link_logo');
            $this->dscRepository->getOssDelFile([$link_logo]);
            @unlink(storage_public($link_logo));

            $data = ['link_logo' => ''];
            $res = PartnerList::where('link_id', $id)->update($data);
            if ($res < 1) {
                $result['error'] = 1;
            }

            return make_json_result($result);
        }

        /*------------------------------------------------------ */
        //-- 编辑排序
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'edit_show_order') {
            $check_auth = check_authz_json('partnerlink');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $id = intval($_POST['id']);
            $order = json_str_iconv(trim($_POST['val']));

            /* 检查输入的值是否合法 */
            if (!preg_match("/^[0-9]+$/", $order)) {
                return make_json_error(sprintf($GLOBALS['_LANG']['enter_int'], $order));
            } else {
                $data = ['show_order' => $order];
                $res = PartnerList::where('link_id', $id)->update($data);
                if ($res > 0) {
                    clear_cache_files();
                    return make_json_result(stripslashes($order));
                }
            }
        }
        /*------------------------------------------------------ */
        //-- 删除合作伙伴
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'remove') {
            $check_auth = check_authz_json('partnerlink');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $id = intval($_GET['id']);

            /* 获取链子LOGO,并删除 */
            $link_logo = PartnerList::where('link_id', $id)->value('link_logo');
            $this->dscRepository->getOssDelFile([$link_logo]);
            @unlink(storage_public($link_logo));
            PartnerList::where('link_id', $id)->delete();

            clear_cache_files();
            admin_log('', 'remove', 'partnerlink');

            $url = 'friend_partner.php?act=query&' . str_replace('act=remove', '', request()->server('QUERY_STRING'));

            return dsc_header("Location: $url\n");
        }
    }
}
