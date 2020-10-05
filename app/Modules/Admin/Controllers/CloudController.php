<?php

namespace App\Modules\Admin\Controllers;

use App\Libraries\Transport;
use App\Repositories\Common\SessionRepository;

/**
 *  云服务接口
 */
class CloudController extends InitController
{
    public function index()
    {
        return make_json_result('0');
        //阻断云中心通信 by wanganlin

        $data['api_ver'] = '1.0';
        $data['version'] = VERSION;
        $data['patch'] = file_get_contents(storage_public(ADMIN_PATH . "/patch_num"));
        $data['ecs_lang'] = $GLOBALS['_CFG']['lang'];
        $data['release'] = RELEASE;
        $data['charset'] = strtoupper(EC_CHARSET);
        $data['certificate_id'] = $GLOBALS['_CFG']['certificate_id'];
        $data['token'] = md5($GLOBALS['_CFG']['token']);
        $data['certi'] = $GLOBALS['_CFG']['certi'];
        $data['php_ver'] = PHP_VERSION;
        $data['mysql_ver'] = $this->db->version();
        $data['shop_url'] = urlencode($this->dsc->url());
        $data['admin_url'] = urlencode($this->dsc->url() . ADMIN_PATH);
        $data['sess_id'] = app(SessionRepository::class)->getSessionId();
        $data['stamp'] = mktime();
        $data['ent_id'] = $GLOBALS['_CFG']['ent_id'];
        $data['ent_ac'] = $GLOBALS['_CFG']['ent_ac'];
        $data['ent_sign'] = $GLOBALS['_CFG']['ent_sign'];
        $data['ent_email'] = $GLOBALS['_CFG']['ent_email'];

        $act = !empty($_REQUEST['act']) ? $_REQUEST['act'] : 'index';

        $must = ['version', 'ecs_lang', 'charset', 'patch', 'stamp', 'api_ver'];
        if ($act == 'menu_api') {
            if (!admin_priv('all', '', false)) {
                return make_json_result('0');
            }
            $api_data = read_static_cache('menu_api');

            if ($api_data === false || (isset($api_data['api_time']) && $api_data['api_time'] < date('Ymd'))) {
                $t = new Transport;
                $apiget = "ver= $data[version] &ecs_lang= $data[ecs_lang] &charset= $data[charset]&ent_id=$data[ent_id]& certificate_id=$data[certificate_id]";

                $api_comment = '';
                $api_str = $api_comment["body"];
                if (!empty($api_str)) {
                    $api_arr = dsc_decode($api_str, true);
                    if (!empty($api_arr) && $api_arr['error'] == 0 && md5($api_arr['content']) == $api_arr['hash']) {
                        $api_arr['content'] = urldecode($api_arr['content']);
                        if ($data['charset'] != 'UTF-8') {
                            $api_arr['content'] = dsc_iconv('UTF-8', $data['charset'], $api_arr['content']);
                        }
                        $api_arr['api_time'] = local_date('Ymd');
                        write_static_cache('menu_api', $api_arr);
                        return make_json_result($api_arr['content']);
                    } else {
                        return make_json_result('0');
                    }
                } else {
                    return make_json_result('0');
                }
            } else {
                return make_json_result($api_data['content']);
            }
        } elseif ($act == 'cloud_remind') {
            $api_data = read_static_cache('cloud_remind');

            if ($api_data === false || (isset($api_data['api_time']) && $api_data['api_time'] < date('Ymd'))) {
                $t = new Transport('-1', 5);
                $apiget = "ver=$data[version]&ecs_lang=$data[ecs_lang]&charset=$data[charset]&certificate_id=$data[certificate_id]&ent_id=$data[ent_id]";
                $api_comment = '';
                $api_str = $api_comment["body"];
                $api_arr = dsc_decode($api_str, true);
                if (!empty($api_str)) {
                    if (!empty($api_arr) && $api_arr['error'] == 0 && md5($api_arr['content']) == $api_arr['hash']) {
                        $api_arr['content'] = urldecode($api_arr['content']);
                        $message = explode('|', $api_arr['content']);
                        $api_arr['content'] = '<li  class="cloud_close">' . $message['0'] . '<img onclick="cloud_close(' . $message['1'] . ')" src="images/no.gif"></li>';
                        if ($data['charset'] != 'UTF-8') {
                            $api_arr['content'] = dsc_iconv('UTF-8', $data['charset'], $api_arr['content']);
                        }
                        $api_arr['api_time'] = local_date('Ymd');
                        write_static_cache('cloud_remind', $api_arr);
                        return make_json_result($api_arr['content']);
                    } else {
                        return make_json_result('0');
                    }
                } else {
                    return make_json_result('0');
                }
            } else {
                return make_json_result($api_data['content']);
            }
        } elseif ($act == 'close_remind') {
            $remind_id = $_REQUEST['remind_id'];
            $t = new Transport('-1', 5);
            $apiget = "ver= $data[version] &ecs_lang= $data[ecs_lang] &charset= $data[charset] &certificate_id=$data[certificate_id]&ent_id=$data[ent_id]&remind_id=$remind_id";
            $api_comment = '';
            $api_str = $api_comment["body"];
            $api_arr = [];
            $api_arr = dsc_decode($api_str, true);
            if (!empty($api_str)) {
                if (!empty($api_arr) && $api_arr['error'] == 0 && md5($api_arr['content']) == $api_arr['hash']) {
                    $api_arr['content'] = urldecode($api_arr['content']);
                    if ($data['charset'] != 'UTF-8') {
                        $api_arr['content'] = dsc_iconv('UTF-8', $data['charset'], $api_arr['content']);
                    }
                    if (admin_priv('all', '', false)) {
                        $apiget .= "&act=close_remind&ent_ac=$data[ent_ac]";
                        $result = '';
                        $api_str = $result["body"];

                        $api_arr = [];
                        $api_arr = dsc_decode($api_str, true);
                        $api_arr['content'] = urldecode($api_arr['content']);
                        if ($data['charset'] != 'UTF-8') {
                            $api_arr['content'] = dsc_iconv('UTF-8', $data['charset'], $api_arr['content']);
                        }
                        if ($api_arr['error'] == 1) {
                            $message = explode('|', $api_arr['content']);
                            $api_arr['content'] = '<li  class="cloud_close">' . $message['0'] . '&nbsp;&nbsp;&nbsp;&nbsp;' . $message['2'] . '</li>';
                            return make_json_result($api_arr['content']);
                        } else {
                            clear_all_files();
                            return make_json_result('0');
                        }
                    } else {
                        $message = explode('|', $api_arr['content']);

                        $api_arr['content'] = '<li  class="cloud_close">' . $message['0'] . '&nbsp;&nbsp;&nbsp;&nbsp;' . $GLOBALS['_LANG']['cloud_no_priv'] . '<img onclick="cloud_close( ' . $message['1'] . ')" src="images/no.gif"></li>';

                        return make_json_result($api_arr['content']);
                    }
                } else {
                    return make_json_result('0');
                }
            }
        } else {
            admin_priv('all');
            if (empty($_GET['act'])) {
                $act = 'index';
            } else {
                $query = '';
                $act = trim($_GET['act']);
                foreach ($_GET as $k => $v) {
                    if (array_key_exists($k, $data)) {
                        $query .= '&' . $k . '=' . $data[$k];
                    }
                }
            }
            if (!empty($_GET['link'])) {
                $url = parse_url($_GET['link']);
                if (!empty($url['host'])) {
                    return dsc_header("Location: " . $url['scheme'] . "://" . $url['host'] . $url['path'] . "?" . $url['query'] . $query . "\n");
                }
            }

            foreach ($must as $v) {
                $query .= '&' . $v . '=' . $data[$v];
            }
        }
    }
}
