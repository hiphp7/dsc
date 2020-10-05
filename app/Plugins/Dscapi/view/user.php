<?php

/* 获取传值 */
$user_id = isset($_REQUEST['user_id']) ? $base->get_intval($_REQUEST['user_id']) : -1;                  //会员ID
$user_name = isset($_REQUEST['user_name']) ? $base->get_addslashes($_REQUEST['user_name']) : -1;            //会员名称

$mobile = -1;
//会员手机号
if (isset($_REQUEST['mobile'])) {
    $mobile = isset($_REQUEST['mobile']) ? $base->get_addslashes($_REQUEST['mobile']) : -1;
} elseif (isset($_REQUEST['mobile_phone'])) {
    $mobile = isset($_REQUEST['mobile_phone']) ? $base->get_addslashes($_REQUEST['mobile_phone']) : -1;
}

$rank_id = isset($_REQUEST['rank_id']) ? $base->get_intval($_REQUEST['rank_id']) : -1;                  //等级ID
$rank_name = isset($_REQUEST['rank_name']) ? $base->get_addslashes($_REQUEST['rank_name']) : -1;            //等级名称
$address_id = isset($_REQUEST['address_id']) ? $base->get_intval($_REQUEST['address_id']) : -1;         //收货地址ID

$val = array(
    'user_id' => $user_id,
    'user_name' => $user_name,
    'mobile' => $mobile,
    'rank_id' => $rank_id,
    'rank_name' => $rank_name,
    'address_id' => $address_id,
    'user_select' => $data,
    'page_size' => $page_size,
    'page' => $page,
    'sort_by' => $sort_by,
    'sort_order' => $sort_order,
    'format' => $format
);

/* 初始化商品类 */
$user = new \App\Plugins\Dscapi\app\controller\user($val);

switch ($method) {

    /**
     * 获取会员列表
     */
    case 'dsc.user.list.get':

        $table = array(
            'users' => 'users'
        );

        $result = $user->get_user_list($table);

        die($result);
        break;

    /**
     * 获取单条会员信息
     */
    case 'dsc.user.info.get':

        $table = array(
            'users' => 'users'
        );

        $result = $user->get_user_info($table);

        die($result);
        break;

    /**
     * 插入会员信息
     */
    case 'dsc.user.insert.post':

        $table = array(
            'users' => 'users'
        );

        $result = $user->get_user_insert($table);

        die($result);
        break;

    /**
     * 更新会员信息
     */
    case 'dsc.user.update.post':

        $table = array(
            'users' => 'users'
        );

        $result = $user->get_user_update($table);

        die($result);
        break;

    /**
     * 删除会员信息
     */
    case 'dsc.user.del.get':

        $table = array(
            'users' => 'users'
        );

        $result = $user->get_user_delete($table);

        die($result);
        break;

    /**
     * 获取会员等级列表
     */
    case 'dsc.user.rank.list.get':

        $table = array(
            'rank' => 'user_rank'
        );

        $result = $user->get_user_rank_list($table);

        die($result);
        break;

    /**
     * 获取单条会员等级信息
     */
    case 'dsc.user.rank.info.get':

        $table = array(
            'rank' => 'user_rank'
        );

        $result = $user->get_user_rank_info($table);

        die($result);
        break;

    /**
     * 插入会员等级信息
     */
    case 'dsc.user.rank.insert.post':

        $table = array(
            'rank' => 'user_rank'
        );

        $result = $user->get_user_rank_insert($table);

        die($result);
        break;

    /**
     * 更新会员等级信息
     */
    case 'dsc.user.rank.update.post':

        $table = array(
            'rank' => 'user_rank'
        );

        $result = $user->get_user_rank_update($table);

        die($result);
        break;

    /**
     * 删除会员等级信息
     */
    case 'dsc.user.rank.del.get':

        $table = array(
            'rank' => 'user_rank'
        );

        $result = $user->get_user_rank_delete($table);

        die($result);
        break;

    /**
     * 获取会员收货地址列表
     */
    case 'dsc.user.address.list.get':

        $table = array(
            'address' => 'user_address'
        );

        $result = $user->get_user_address_list($table);

        die($result);
        break;

    /**
     * 获取单条会员收货地址信息
     */
    case 'dsc.user.address.info.get':

        $table = array(
            'address' => 'user_address'
        );

        $result = $user->get_user_address_info($table);

        die($result);
        break;

    /**
     * 插入会员收货地址信息
     */
    case 'dsc.user.address.insert.post':

        $table = array(
            'address' => 'user_address'
        );

        $result = $user->get_user_address_insert($table);

        die($result);
        break;

    /**
     * 更新会员收货地址信息
     */
    case 'dsc.user.address.update.post':

        $table = array(
            'address' => 'user_address'
        );

        $result = $user->get_user_address_update($table);

        die($result);
        break;

    /**
     * 删除会员收货地址信息
     */
    case 'dsc.user.address.del.get':

        $table = array(
            'address' => 'user_address'
        );

        $result = $user->get_user_address_delete($table);

        die($result);
        break;

    default:

        echo "非法接口连接";
        break;
}
