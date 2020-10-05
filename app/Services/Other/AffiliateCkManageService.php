<?php

namespace App\Services\Other;

use App\Models\AffiliateLog;
use App\Models\OrderInfo;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\DscRepository;
use App\Repositories\Common\TimeRepository;

class AffiliateCkManageService
{
    protected $baseRepository;
    protected $timeRepository;
    protected $dscRepository;

    public function __construct(
        BaseRepository $baseRepository,
        TimeRepository $timeRepository,
        DscRepository $dscRepository
    )
    {
        $this->baseRepository = $baseRepository;
        $this->timeRepository = $timeRepository;
        $this->dscRepository = $dscRepository;
    }

    public function getAffiliatCk()
    {
        $affiliate = $GLOBALS['_CFG']['affiliate'] ? unserialize($GLOBALS['_CFG']['affiliate']) : [];
        empty($affiliate) && $affiliate = [];
        $separate_by = $affiliate['config']['separate_by'];

        $order_sn = isset($_REQUEST['order_sn']) && !empty($_REQUEST['order_sn']) ? addslashes($_REQUEST['order_sn']) : '';
        $auid = isset($_REQUEST['auid']) && !empty($_REQUEST['auid']) ? intval($_REQUEST['auid']) : 0;

        if (!empty($affiliate['on'])) {
            if (empty($separate_by)) {
                //推荐注册分成
                $row = OrderInfo::where('user_id', '>', 0);

                $row = $row->where(function ($query) {
                    $query = $query->where(function ($query) {
                        $query = $query->whereHas('getUsers', function ($query) {
                            $query->where('parent_id', '>', 0);
                        });

                        $query->where('is_separate', 0);
                    });

                    $query->orWhere('is_separate', '>', 0);
                });

                /*
                    SQL解释：

                    列出同时满足以下条件的订单分成情况：
                    1、有效订单o.user_id > 0
                    2、满足以下情况之一：
                        a.有用户注册上线的未分成订单 u.parent_id > 0 AND o.is_separate = 0
                        b.已分成订单 o.is_separate > 0

                */
            } else {
                //推荐订单分成
                $row = OrderInfo::where('user_id', '>', 0);

                $row = $row->where(function ($query) {
                    $query->where(function ($query) {
                        $query->where('parent_id', '>', 0)
                            ->where('is_separate', 0);
                    });

                    $query->orWhere('is_separate', '>', 0);
                });

                /*
                    SQL解释：

                    列出同时满足以下条件的订单分成情况：
                    1、有效订单o.user_id > 0
                    2、满足以下情况之一：
                        a.有订单推荐上线的未分成订单 o.parent_id > 0 AND o.is_separate = 0
                        b.已分成订单 o.is_separate > 0

                */
            }
        } else {
            //推荐订单分成
            $row = OrderInfo::where('user_id', '>', 0)
                ->where('is_separate', '>', 0);
        }

        if (isset($_REQUEST['status'])) {
            $status = intval($_REQUEST['status']);
            $row = $row->where('is_separate', $status);
            $filter['status'] = $status;
        }

        $where = [];
        if ($order_sn) {
            $where['order_sn'] = $order_sn;
            $row = $row->where(function ($query) use ($where) {
                $query = $query->where('order_sn', 'like', '%' . $where['order_sn'] . '%');
                $query->orWhere(function ($query) use ($where) {
                    $query->whereHas('getUsers', function ($query) use ($where) {
                        $query->where('user_name', 'like', '%' . $where['order_sn'] . '%')
                            ->orWhere('nick_name', 'like', '%' . $where['order_sn'] . '%');
                    });
                });
            });
            $filter['order_sn'] = $order_sn;
        }
        if ($auid > 0) {
            $where['auid'] = $auid;
            $row = $row->whereHas('getAffiliateLog', function ($query) use ($where) {
                $query->where('user_id', $where['auid']);
            });
        }

        $row = $row->where('main_count', 0)->where('ru_id', 0);

        $res = $record_count = $row;

        $filter['record_count'] = $record_count->count();

        /* 分页大小 */
        $filter = page_and_size($filter);

        $res = $res->with([
            'getUsers',
            'getAffiliateLog'
        ]);

        $res = $res->orderBy('order_id', 'desc');

        if ($filter['start'] > 0) {
            $res = $res->skip($filter['start']);
        }

        if ($filter['page_size']) {
            $res = $res->take($filter['page_size']);
        }

        $query = $this->baseRepository->getToArrayGet($res);

        $logdb = [];
        if ($query) {
            foreach ($query as $rt) {
                $user = $rt['get_users'];
                $affiliate_log = $rt['get_affiliate_log'];

                $rt['log_id'] = $affiliate_log['log_id'] ?? 0;
                $rt['suid'] = $affiliate_log['user_id'] ?? 0;
                $rt['auser'] = $affiliate_log['user_name'] ?? '';

                if (isset($GLOBALS['_CFG']['show_mobile']) && $GLOBALS['_CFG']['show_mobile'] == 0) {
                    $rt['auser'] = $this->dscRepository->stringToStar($rt['auser']);
                }

                $rt['money'] = $affiliate_log['money'] ?? 0;
                $rt['point'] = $affiliate_log['point'] ?? 0;
                $rt['separate_type'] = $affiliate_log['separate_type'] ?? 0;
                $rt['up'] = $user['parent_id'] ?? 0;

                if (empty($separate_by) && $rt['up'] > 0) {
                    //按推荐注册分成
                    $rt['separate_able'] = 1;
                } elseif (!empty($separate_by) && $rt['parent_id'] > 0) {
                    //按推荐订单分成
                    $rt['separate_able'] = 1;
                }

                if (!empty($rt['suid'])) {
                    //在affiliate_log有记录
                    $rt['info'] = sprintf($GLOBALS['_LANG']['separate_info2'], $rt['suid'], $rt['auser'], $rt['money'], $rt['point']);
                    if ($rt['separate_type'] == -1 || $rt['separate_type'] == -2) {
                        //已被撤销
                        $rt['is_separate'] = 3;
                        $rt['info'] = "<s>" . $rt['info'] . "</s>";
                    }
                }

                $logdb[] = $rt;
            }
        }

        $arr = ['logdb' => $logdb, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']];

        return $arr;
    }

    /**
     * 分成记录
     *
     * @param $oid
     * @param $uid
     * @param $username
     * @param $money
     * @param $point
     * @param $separate_by
     */
    public function writeAffiliateLog($oid, $uid, $username, $money, $point, $separate_by)
    {
        if ($oid) {
            $time = $this->timeRepository->getGmTime();
            $data = [
                'order_id' => $oid,
                'user_id' => $uid,
                'user_name' => $username,
                'time' => $time,
                'money' => $money,
                'point' => $point,
                'separate_type' => $separate_by
            ];
            AffiliateLog::insert($data);
        }
    }

    /**
     * 分成
     *
     * @param int $logid
     * @return array
     */
    public function getAffiliateLog($logid = 0)
    {
        $row = AffiliateLog::where('log_id', $logid);
        $row = $this->baseRepository->getToArrayFirst($row);

        return $row;
    }
}
