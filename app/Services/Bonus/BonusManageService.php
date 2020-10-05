<?php

namespace App\Services\Bonus;

use App\Libraries\Template;
use App\Models\BonusType;
use App\Models\EmailSendlist;
use App\Models\Goods;
use App\Models\MailTemplates;
use App\Models\UserBonus;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\DscRepository;
use App\Repositories\Common\TimeRepository;
use App\Services\Common\CommonManageService;
use App\Services\Merchant\MerchantCommonService;

class BonusManageService
{
    protected $commonManageService;
    protected $baseRepository;
    protected $timeRepository;
    protected $config;
    protected $dscRepository;
    protected $template;
    protected $merchantCommonService;

    public function __construct(
        CommonManageService $commonManageService,
        BaseRepository $baseRepository,
        TimeRepository $timeRepository,
        DscRepository $dscRepository,
        Template $template,
        MerchantCommonService $merchantCommonService
    )
    {
        $this->commonManageService = $commonManageService;
        $this->baseRepository = $baseRepository;
        $this->timeRepository = $timeRepository;
        $this->dscRepository = $dscRepository;
        $this->template = $template;
        $this->merchantCommonService = $merchantCommonService;
        $this->config = $this->dscRepository->dscConfig();
    }

    /**
     * 获取红包类型列表
     * @access  public
     * @return void
     */
    public function getTypeList()
    {
        $seller = $this->commonManageService->getAdminIdSeller();

        /* 获得所有红包类型的发放数量 */
        $res = UserBonus::selectRaw("bonus_type_id, COUNT(*) AS sent_count")
            ->groupBy('bonus_type_id');
        $res = $this->baseRepository->getToArrayGet($res);

        $sent_arr = [];
        if ($res) {
            foreach ($res as $row) {
                $sent_arr[$row['bonus_type_id']] = $row['sent_count'];
            }
        }

        /* 获得所有红包类型的发放数量 */
        $res = UserBonus::selectRaw("bonus_type_id, COUNT(*) AS used_count")
            ->where('used_time', '>', 0)
            ->groupBy('bonus_type_id');
        $res = $this->baseRepository->getToArrayGet($res);

        $used_arr = [];
        if ($res) {
            foreach ($res as $row) {
                $used_arr[$row['bonus_type_id']] = $row['used_count'];
            }
        }

        /* 过滤条件 */
        $filter['keyword'] = empty($_REQUEST['keyword']) ? '' : trim($_REQUEST['keyword']);
        if (isset($_REQUEST['is_ajax']) && $_REQUEST['is_ajax'] == 1) {
            $filter['keyword'] = json_str_iconv($filter['keyword']);
        }

        $filter['ru_id'] = isset($_REQUEST['ru_id']) && !empty($_REQUEST['ru_id']) ? intval($_REQUEST['ru_id']) : $seller['ru_id'];
        $filter['sort_by'] = empty($_REQUEST['sort_by']) ? 'type_id' : trim($_REQUEST['sort_by']);
        $filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'DESC' : trim($_REQUEST['sort_order']);
        $filter['review_status'] = empty($_REQUEST['review_status']) ? 0 : intval($_REQUEST['review_status']);
        $filter['use_type'] = empty($_REQUEST['use_type']) ? 0 : intval($_REQUEST['use_type']);
        $filter['seller_list'] = isset($_REQUEST['seller_list']) && !empty($_REQUEST['seller_list']) ? 1 : 0;  //商家和自营订单标识

        //卖场 start
        $filter['rs_id'] = empty($_REQUEST['rs_id']) ? 0 : intval($_REQUEST['rs_id']);
        if ($seller['rs_id'] > 0) {
            $filter['rs_id'] = $seller['rs_id'];
        }
        //卖场 end

        $row = BonusType::whereRaw(1);

        //ecmoban模板堂 --zhuo start
        if ($filter['use_type'] == 1) { //自营
            $row = $row->where('user_id', 0)
                ->where('usebonus_type', 0);
        } elseif ($filter['use_type'] == 2) { //商家\
            $row = $row->where('user_id', '>', 0)
                ->where('usebonus_type', 0);
        } elseif ($filter['use_type'] == 3) { //全场
            $row = $row->where('usebonus_type', 1);
        } elseif ($filter['use_type'] == 4) { //商家自主使用
            $row = $row->where('user_id', $filter['ru_id'])
                ->where('usebonus_type', 0);
        } else {
            if ($filter['ru_id'] > 0) {
                $row = $row->where(function ($query) use ($filter) {
                    $query->where('user_id', $filter['ru_id'])
                        ->orWhere('usebonus_type', 1);
                });
            }
        }

        if (!empty($filter['keyword'])) {
            $row = $row->where('type_name', 'like', '%' . mysql_like_quote($filter['keyword']) . '%');
        }

        if ($filter['review_status']) {
            $row = $row->where('review_status', $filter['review_status']);
        }

        //卖场
        if ($filter['rs_id'] > 0) {
            $row = $this->dscRepository->getWhereRsid($row, 'user_id', $filter['rs_id']);
        }

        //管理员查询的权限 -- 店铺查询 start
        $filter['store_search'] = empty($_REQUEST['store_search']) ? 0 : intval($_REQUEST['store_search']);
        $filter['merchant_id'] = isset($_REQUEST['merchant_id']) ? intval($_REQUEST['merchant_id']) : 0;
        $filter['store_keyword'] = isset($_REQUEST['store_keyword']) ? trim($_REQUEST['store_keyword']) : '';

        if ($filter['store_search'] != 0) {
            if ($filter['ru_id'] == 0) {
                $filter['store_type'] = isset($_REQUEST['store_type']) && !empty($_REQUEST['store_type']) ? intval($_REQUEST['store_type']) : 0;

                if ($filter['store_search'] == 1) {
                    $row = $row->where('user_id', $filter['merchant_id']);
                }

                if ($filter['store_search'] > 1) {
                    $row = $row->whereHas('getMerchantsShopInformation', function ($query) use ($filter) {
                        if ($filter['store_search'] == 2) {
                            $query->where('rz_shopName', 'like', '%' . $filter['store_keyword'] . '%');
                        } elseif ($filter['store_search'] == 3) {
                            $query = $query->where('shoprz_brandName', 'like', '%' . $filter['store_keyword'] . '%');

                            if ($filter['store_type']) {
                                $query->where('shopNameSuffix', $filter['store_type']);
                            }
                        }
                    });
                }
            }
        }
        //管理员查询的权限 -- 店铺查询 end

        //区分商家和自营
        if (!empty($filter['seller_list'])) {
            $row = $row->where('user_id', '>', 0);
        } else {
            $row = $row->where('user_id', 0);
        }

        $res = $record_count = $row;

        $filter['record_count'] = $record_count->count();

        /* 分页大小 */
        $filter = page_and_size($filter);

        $res = $res->orderBy($filter['sort_by'], $filter['sort_order']);

        if ($filter['start'] > 0) {
            $res = $res->skip($filter['start']);
        }

        if ($filter['page_size'] > 0) {
            $res = $res->take($filter['page_size']);
        }

        $res = $this->baseRepository->getToArrayGet($res);

        $arr = [];
        if ($res) {
            foreach ($res as $row) {
                $row['send_by'] = $GLOBALS['_LANG']['send_by'][$row['send_type']];
                $row['send_count'] = isset($sent_arr[$row['type_id']]) ? $sent_arr[$row['type_id']] : 0;
                $row['use_count'] = isset($used_arr[$row['type_id']]) ? $used_arr[$row['type_id']] : 0;

                $row['user_name'] = $this->merchantCommonService->getShopName($row['user_id'], 1);

                $arr[] = $row;
            }
        }

        $arr = ['item' => $arr, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']];

        return $arr;
    }

    /**
     * 查询红包类型的商品列表
     *
     * @access  public
     * @param integer $type_id
     * @return  array
     */
    public function getBonusGoods($type_id = 0)
    {
        $row = Goods::where('bonus_type_id', $type_id);
        $row = $this->baseRepository->getToArrayGet($row);

        return $row;
    }

    /**
     * 取得红包类型信息
     * @param int $bonus_type_id 红包类型id
     * @return  array
     */
    public function bonusTypeInfo($bonus_type_id = 0)
    {
        $row = BonusType::where('type_id', $bonus_type_id);
        $row = $this->baseRepository->getToArrayFirst($row);

        return $row;
    }

    /**
     * 发送红包邮件
     * @param int $bonus_type_id 红包类型id
     * @param array $bonus_id_list 红包id数组
     * @return  int     成功发送数量
     */
    public function sendBonusMail($bonus_type_id, $bonus_id_list)
    {
        /* 取得红包类型信息 */
        $bonus_type = $this->bonusTypeInfo($bonus_type_id);
        if ($bonus_type['send_type'] != SEND_BY_USER) {
            return 0;
        }

        /* 取得属于该类型的红包信息 */
        $bonus_id_list = $this->baseRepository->getExplode($bonus_id_list);

        $bonus_list = [];
        if ($bonus_id_list) {
            $bonus_list = UserBonus::whereIn('bonus_id', $bonus_id_list)
                ->where('order_id', 0);

            $bonus_list = $bonus_list->whereHas('getUsers', function ($query) {
                $query->where('email', '<>', '');
            });

            $bonus_list = $bonus_list->with([
                'getUsers' => function ($query) {
                    $query->select('user_id', 'user_name', 'email');
                }
            ]);

            $bonus_list = $this->baseRepository->getToArrayGet($bonus_list);

            if ($bonus_list) {
                foreach ($bonus_list as $key => $val) {
                    $val = $this->baseRepository->getArrayMerge($val, $val['get_users']);
                    $bonus_list[$key] = $val;
                }
            }
        }

        if (empty($bonus_list)) {
            return 0;
        }

        /* 初始化成功发送数量 */
        $send_count = 0;

        /* 发送邮件 */
        $tpl = get_mail_template('send_bonus');
        $today = $this->timeRepository->getLocalDate($this->config['time_format'], $this->timeRepository->getGmTime());
        foreach ($bonus_list as $bonus) {
            $this->template->assign('user_name', $bonus['user_name']);
            $this->template->assign('shop_name', $this->config['shop_name']);
            $this->template->assign('send_date', $today);
            $this->template->assign('sent_date', $today);
            $this->template->assign('count', 1);
            $this->template->assign('money', $this->dscRepository->getPriceFormat($bonus_type['type_money']));

            $content = $this->template->fetch('str:' . $tpl['template_content']);
            $other = [];
            if ($this->addToMaillist($bonus['user_name'], $bonus['email'], $tpl['template_subject'], $content, $tpl['is_html'], false)) {
                $other['emailed'] = BONUS_MAIL_SUCCEED;
                $send_count++;
            } else {
                $other['emailed'] = BONUS_MAIL_FAIL;
            }

            UserBonus::where('bonus_id', $bonus['bonus_id'])->update($other);
        }

        return $send_count;
    }

    /**
     * 邮件发送信息记录
     *
     * @param $username
     * @param $email
     * @param $subject
     * @param $content
     * @param $is_html
     * @return bool
     */
    public function addToMaillist($username, $email, $subject, $content, $is_html)
    {
        $time = $this->timeRepository->getGmTime();
        $content = addslashes($content);

        $template_id = MailTemplates::where('template_code', 'send_bonus')->value('template_id');
        $template_id = $template_id ? $template_id : 0;

        $other = [
            'email' => $email,
            'template_id' => $template_id,
            'email_content' => $content,
            'pri' => 1,
            'last_send' => $time
        ];
        EmailSendlist::insert($other);

        return true;
    }

    /**
     * 获取用户红包列表
     * @access  public
     * @param   $page_param
     * @return void
     */
    public function getBonusList()
    {
        /* 查询条件 */
        $filter['sort_by'] = empty($_REQUEST['sort_by']) ? 'bonus_id' : trim($_REQUEST['sort_by']);
        $filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'DESC' : trim($_REQUEST['sort_order']);
        $filter['bonus_type'] = empty($_REQUEST['bonus_type']) ? 0 : intval($_REQUEST['bonus_type']);

        $row = UserBonus::whereRaw(1);

        if ($filter['bonus_type']) {
            $row = $row->where('bonus_type_id', $filter['bonus_type']);
        }

        $res = $record_count = $row;

        $filter['record_count'] = $record_count->count();

        /* 分页大小 */
        $filter = page_and_size($filter);

        $res = $res->with([
            'getBonusType',
            'getUsers',
            'getOrderInfo'
        ]);

        $res = $res->orderBy($filter['sort_by'], $filter['sort_order']);

        if ($filter['start'] > 0) {
            $res = $res->skip($filter['start']);
        }

        if ($filter['page_size'] > 0) {
            $res = $res->take($filter['page_size']);
        }

        $res = $this->baseRepository->getToArrayGet($res);

        if ($res) {
            foreach ($res as $key => $val) {
                $bonus_type = $val['get_bonus_type'];
                $users = $val['get_users'];
                $order_info = $val['get_order_info'];

                $val['user_name'] = $users['user_name'] ?? '';
                $val['email'] = $users['email'] ?? '';

                if ($row && isset($GLOBALS['_CFG']['show_mobile']) && $GLOBALS['_CFG']['show_mobile'] == 0) {
                    $val['user_name'] = $this->dscRepository->stringToStar($val['user_name']);
                    $val['email'] = $this->dscRepository->stringToStar($val['email']);
                }

                $val['type_name'] = $bonus_type['type_name'] ?? '';
                $val['order_sn'] = $order_info['order_sn'] ?? '';

                $res[$key] = $val;

                $res[$key]['used_time'] = $val['used_time'] == 0 ?
                    $GLOBALS['_LANG']['no_use'] : $this->timeRepository->getLocalDate($GLOBALS['_CFG']['date_format'], $val['used_time']);
                $res[$key]['emailed'] = $GLOBALS['_LANG']['mail_status'][$val['emailed']];
            }
        }

        $arr = ['item' => $res, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']];

        return $arr;
    }
}
