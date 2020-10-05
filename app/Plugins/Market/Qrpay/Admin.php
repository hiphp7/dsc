<?php

namespace App\Plugins\Market\Qrpay;

use App\Http\Controllers\Wechat\PluginController;
use App\Models\Payment;
use App\Models\QrpayDiscounts;
use App\Models\QrpayLog;
use App\Models\QrpayManage;
use App\Models\QrpayTag;
use App\Models\Users;
use App\Services\Qrpay\QrpayService;
use Endroid\QrCode\QrCode;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

/**
 * 收款二维码后台模块
 * Class Admin
 * @package App\Plugins\Market\Qrpay
 */
class Admin extends PluginController
{
    protected $marketing_type = ''; // 活动类型
    protected $wechat_id = 0; // 微信通ID
    protected $page_num = 10; // 分页数量
    protected $wechat_ru_id = 0; // 商家id

    // 配置
    protected $cfg = [];

    public function __construct($cfg = [])
    {
        parent::__construct();
        $this->cfg = $cfg;
        $this->cfg['plugin_path'] = 'Market';
        $this->plugin_name = $this->marketing_type = $cfg['keywords'];
        $this->wechat_id = $cfg['wechat_id'];
        $this->wechat_ru_id = isset($cfg['wechat_ru_id']) ? $cfg['wechat_ru_id'] : 0;
        $this->page_num = isset($cfg['page_num']) ? $cfg['page_num'] : 10;

        $this->plugin_assign('ru_id', $this->wechat_ru_id);

        if ($this->wechat_ru_id > 0) {
            // 查询商家管理员
            $this->assign('admin_info', $this->cfg['seller']);
            $this->assign('ru_id', $this->cfg['seller']['ru_id']);
            $this->assign('seller_name', $this->cfg['seller']['user_name']);

            //判断编辑个人资料权限
            $this->assign('privilege_seller', $this->cfg['privilege_seller']);
            // 商家菜单列表
            $this->assign('seller_menu', $this->cfg['menu']);
            // 当前选择菜单
            $this->assign('menu_select', $this->cfg['menu_select']);
            // 当前位置
            $this->assign('postion', $this->cfg['postion']);
        }

        $this->plugin_assign('page_num', $this->page_num);
        $this->plugin_assign('config', $this->cfg);
    }

    /**
     * 活动列表
     */
    public function marketList()
    {
        $filter['type'] = $this->marketing_type;
        $offset = $this->pageLimit($this->wechat_ru_id > 0 ? route('seller/wechat/market_list', $filter) : route('admin/wechat/market_list', $filter), $this->page_num);

        $model = QrpayManage::where(['ru_id' => $this->wechat_ru_id]);

        $total = $model->count();

        $list = $model->select('id', 'qrpay_name', 'type', 'discount_id', 'tag_id', 'qrpay_status', 'qrpay_code', 'add_time')->where(['ru_id' => $this->wechat_ru_id])
            ->offset($offset['start'])
            ->limit($offset['limit'])
            ->orderBy('id', 'DESC')
            ->get();
        $list = $list ? $list->toArray() : [];

        if ($list) {
            foreach ($list as $k => $v) {
                $list[$k]['add_time'] = local_date('Y-m-d H:i:s', $v['add_time']);
                $list[$k]['type'] = $v['type'] == 1 ? '指定金额收款码' : '自助收款码';
                $list[$k]['qrpay_code'] = $this->wechatHelperService->get_wechat_image_path($v['qrpay_code']);
                $list[$k]['discounts_name'] = $v['discount_id'] > 0 ? $this->get_qrpay_discounts($v['discount_id']) : '-';
                $list[$k]['tag_name'] = $v['tag_id'] > 0 ? $this->get_qrpay_tag($v['tag_id']) : '-';
            }
        }

        $this->plugin_assign('page', $this->pageShow($total));
        $this->plugin_assign('list', $list);
        return $this->plugin_display('market_list', $this->_data);
    }

    /**
     * 添加与编辑
     * @return
     */
    public function marketEdit()
    {
        // 提交
        if (request()->isMethod('POST')) {
            $id = request()->input('id');
            $data = request()->input('data');

            if (empty($data['qrpay_name']) || strlen($data['qrpay_name']) >= 32) {
                $json_result = ['error' => 1, 'msg' => '收款码名称必填，并且须少于32个字符'];
                return response()->json($json_result);
            }
            // 验证收款码金额
            if (isset($data['type']) && $data['type'] == 2) {
                if (empty($data['amount'])) {
                    $json_result = ['error' => 1, 'msg' => '指定收款码金额不能为空'];
                    return response()->json($json_result);
                }
            }

            // 生成收款二维码
            $data['qrpay_code'] = $this->creatQrpayCode($id);

            //更新
            if ($id) {
                QrpayManage::where(['id' => $id, 'ru_id' => $this->wechat_ru_id])->update($data);
                $json_result = ['error' => 0, 'msg' => L('market_edit') . L('success'), 'url' => $this->wechat_ru_id > 0 ? route('seller/wechat/market_list', ['type' => $this->marketing_type]) : route('admin/wechat/market_list', ['type' => $this->marketing_type])];
                return response()->json($json_result);
            } else {
                //添加活动
                $data['add_time'] = $this->timeRepository->getGmTime();
                $data['ru_id'] = $this->wechat_ru_id;
                $data['amount'] = $data['amount'] ?? '';
                QrpayManage::insert($data);
                $json_result = ['error' => 0, 'msg' => L('market_add') . L('success'), 'url' => $this->wechat_ru_id > 0 ? route('seller/wechat/market_list', ['type' => $this->marketing_type]) : route('admin/wechat/market_list', ['type' => $this->marketing_type])];
                return response()->json($json_result);
            }
        }

        // 显示
        $info = [];
        $id = isset($this->cfg['market_id']) ? $this->cfg['market_id'] : '';
        if (!empty($id)) {
            $info = QrpayManage::select('id', 'qrpay_name', 'type', 'amount', 'discount_id', 'tag_id', 'qrpay_status', 'qrpay_code', 'add_time')
                ->where(['id' => $id, 'ru_id' => $this->wechat_ru_id])
                ->first();
            $info = $info ? $info->toArray() : [];

            if ($info) {
                $info['add_time'] = local_date('Y-m-d H:i:s', $info['add_time']);
            } else {
                return $this->message('数据不存在', $this->wechat_ru_id > 0 ? route('seller/wechat/market_list', ['type' => $this->marketing_type]) : route('admin/wechat/market_list', ['type' => $this->marketing_type]), 2, $this->wechat_ru_id);
            }
        } else {
            $info['type'] = 0;
        }

        $info['ru_id'] = $this->wechat_ru_id;

        $discounts_list = $this->get_qrpay_discounts();
        $tag_list = $this->get_qrpay_tag();
        $this->plugin_assign('discounts_list', $discounts_list);
        $this->plugin_assign('tag_list', $tag_list);

        $this->plugin_assign('info', $info);
        return $this->plugin_display('market_edit', $this->_data);
    }

    /**
     * 生成收款二维码
     *
     * @param int $id
     * @return string
     */
    public function creatQrpayCode($id = 0)
    {
        $lastId = QrpayManage::orderBy('id', 'DESC')->value('id');
        $id = $id > 0 ? $id : $lastId + 1;

        //二维码内容
        $url = dsc_url('/#/qrpay') . '?' . http_build_query(['id' => $id], '', '&');

        // 生成的文件位置
        $path = storage_public('data/attached/qrpay/');
        // 水印logo
        $water_logo = public_path('assets/mobile/img/timg.jpg');
        // 输出二维码路径
        $qrcode = $path . 'qrpay_' . $this->wechat_ru_id . $id . 'Q8.png';

        if (!is_dir($path)) {
            @mkdir($path, 0777);
        }

        if (!file_exists($qrcode)) {
            $qrCode = new QrCode($url);

            $qrCode->setSize(357);
            $qrCode->setMargin(5);
            $qrCode->setLogoPath($water_logo); // 默认居中
            $qrCode->setLogoWidth(50);
            $qrCode->writeFile($qrcode); // 保存二维码

            // 同步OSS数据
            $this->ossMirror('data/attached/qrpay/' . basename($qrcode), true);
        }

        return 'data/attached/qrpay/' . basename($qrcode);
    }

    /**
     * 重置收款码
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function marketResetQrpay()
    {
        if (request()->isMethod('GET')) {
            $id = request()->input('id', 0);
            $res = QrpayManage::select('qrpay_code', 'qrpay_name')->where(['id' => $id, 'ru_id' => $this->wechat_ru_id])->first();
            $res = $res ? $res->toArray() : [];
            if ($res && !empty($res['qrpay_code'])) {
                // 删除原二维码
                $this->remove($res['qrpay_code']);
                // 生成新二维码
                $data['qrpay_code'] = $this->creatQrpayCode($id);
                QrpayManage::where(['id' => $id, 'ru_id' => $this->wechat_ru_id])->update($data);
                return response()->json(['error' => 0, 'msg' => '重置二维码' . L('success')]);
            }
            return response()->json(['error' => 1, 'msg' => '重置二维码' . L('fail')]);
        }
    }

    /**
     * 下载收款码
     */
    public function marketDownloadQrpay()
    {
        $id = request()->input('id', 0);
        $res = QrpayManage::select('qrpay_code', 'qrpay_name')->where(['id' => $id, 'ru_id' => $this->wechat_ru_id])->first();
        $res = $res ? $res->toArray() : [];

        if (empty($res)) {
            return $this->message('数据不存在', null, 2, $this->wechat_ru_id);
        }

        $file = $res['qrpay_code'];
        $filename = storage_public($res['qrpay_code']);

        // 开启OSS 且本地没有图片的处理
        if ($this->config['open_oss'] == 1 && !file_exists($filename)) {
            $filelist = ['0' => $file];
            $this->BatchDownloadOss($filelist);
        }
        // 文件存在 则下载
        if (file_exists($filename)) {
            return $this->file_download($file);
        } else {
            return $this->message(L('file_not_exist'), null, 2, $this->wechat_ru_id);
        }
    }

    /**
     * 收款记录列表
     * @return
     */
    public function marketQrpayLogList()
    {
        $id = request()->input('id', 0);
        $handler = request()->input('handler', '');
        $function = request()->input('function', '');

        $filter['type'] = $this->marketing_type;
        $filter['function'] = $function;
        $offset = $this->pageLimit($this->wechat_ru_id > 0 ? route('seller/wechat/data_list', $filter) : route('admin/wechat/data_list', $filter), $this->page_num);

        $model = QrpayLog::where('ru_id', $this->wechat_ru_id);

        if ($id) {
            $model = $model->where('qrpay_id', $id);
        }

        if (request()->isMethod('POST')) {
            // 搜索
            $keyword = request()->input('keyword', '');
            if ($keyword) {
                $model = $model->where('pay_order_sn', 'like', '%' . $keyword . '%');
            }
        }
        $map = [];
        $qr_type = request()->input('qr_type', 0);
        if ($qr_type) {
            $map['type'] = $qr_type == 2 ? 0 : 1;
        }

        $qr_tag = request()->input('qr_tag', 0);
        if ($qr_tag) {
            $map['tag_id'] = $qr_tag;
        }

        $model = $model->whereHas('getQrpayManage', function ($query) use ($map) {
            $query->where(function ($query) use ($map) {
                if (isset($map['type'])) {
                    return $query->where('type', $map['type']);
                }
                if (isset($map['tag_id'])) {
                    return $query->where('tag_id', $map['tag_id']);
                }
            });
        });

        $model = $model->with(['getQrpayManage' => function ($query) use ($map) {
            $query->select('id', 'type', 'tag_id')->where(function ($query) use ($map) {
                if (isset($map['type'])) {
                    return $query->where('type', $map['type']);
                }
                if (isset($map['tag_id'])) {
                    return $query->where('tag_id', $map['tag_id']);
                }
            });
        }]);

        $total = $model->count();

        $list = $model->orderBy('id', 'DESC')
            ->orderBy('add_time', 'DESC')
            ->offset($offset['start'])
            ->limit($offset['limit'])
            ->get();
        $list = $list ? $list->toArray() : [];

        foreach ($list as $key => $value) {
            $value = collect($value)->merge($value['get_qrpay_manage'])->except('get_qrpay_manage')->all(); // 合并且移除
            $list[$key]['add_time'] = local_date('Y-m-d H:i', $value['add_time']);
            $list[$key]['user_name'] = $this->get_user_name($value['pay_user_id'], $value['openid']);
            $list[$key]['payment_code'] = $this->get_payment_name($value['payment_code']);
            $list[$key]['pay_status'] = ($value['pay_status'] == 1) ? '已支付' : '未支付';
            $list[$key]['is_settlement'] = ($value['is_settlement'] == 1) ? '已结算' : '未结算';
            $list[$key]['qrpay_type'] = (isset($value['type']) && $value['type'] == 0) ? '自助收款码' : '指定金额收款码';
            $list[$key]['tag_name'] = (isset($value['tag_id']) && $value['tag_id'] > 0) ? $this->get_qrpay_tag($value['tag_id']) : '-';
        }
        $this->plugin_assign('ru_id', $this->wechat_ru_id);
        $this->plugin_assign('qr_type', $qr_type);
        $this->plugin_assign('qr_tag', $qr_tag);

        // 筛选标签列表
        $tag_list = $this->get_qrpay_tag();
        $this->plugin_assign('tag_list', $tag_list);

        $this->plugin_assign('list', $list);
        $this->plugin_assign('page', $this->pageShow($total));
        return $this->plugin_display('market_log_list', $this->_data);
    }

    /**
     * 收款记录详情
     * @return
     */
    public function marketQrpayLogInfo()
    {
        if (request()->isMethod('POST')) {
            $json_result = ['error' => 0, 'msg' => '', 'data' => ''];

            $log_id = request()->input('log_id', 0);

            $info = QrpayLog::where(['id' => $log_id, 'ru_id' => $this->wechat_ru_id])->first();
            $info = $info ? $info->toArray() : [];

            if (!empty($info)) {
                $data = (isset($info['notify_data']) && !empty($info['notify_data'])) ? unserialize($info['notify_data']) : [];
                $data['trade_no'] = !empty($info['trade_no']) ? $info['trade_no'] : '';
                $data['amount'] = isset($data['amount']) ? $data['amount'] : 0;
                $data['pay_time'] = isset($data['pay_time']) ? $data['pay_time'] : '';
                $data['buyer_account'] = isset($data['buyer_account']) ? $data['buyer_account'] : (isset($data['buyer_id']) ? $data['buyer_id'] : '');

                $json_result = ['error' => 0, 'msg' => '', 'data' => $data];
                return response()->json($json_result);
            } else {
                $json_result = ['error' => 1, 'msg' => '记录信息不存在'];
                return response()->json($json_result);
            }
        }
    }

    /**
     * 导出收款记录到Excel
     */
    public function marketExportQrpayLog()
    {
        if (request()->isMethod('POST')) {
            $starttime = request()->input('starttime', '');
            $endtime = request()->input('endtime', '');
            $this->wechat_ru_id = request()->input('ru_id', 0);
            if (empty($starttime) || empty($endtime)) {
                return $this->message('选择时间不能为空', null, 2, $this->wechat_ru_id);
            }
            if ($starttime > $endtime) {
                return $this->message('开始时间不能大于结束时间', null, 2, $this->wechat_ru_id);
            }
            $starttime = local_strtotime($starttime);
            $endtime = local_strtotime($endtime);

            $model = QrpayLog::whereBetween('add_time', [$starttime, $endtime]);

            if ($this->wechat_ru_id > 0) {
                $model = $model->where('ru_id', $this->wechat_ru_id);
            }

            $model = $model->with(['getQrpayManage' => function ($query) {
                $query->select('id', 'type', 'tag_id');
            }]);

            $list = $model->orderBy('add_time', 'DESC')
                ->get();
            $list = $list ? $list->toArray() : [];

            if ($list) {
                foreach ($list as $key => $value) {
                    $value = collect($value)->merge($value['get_qrpay_manage'])->except('get_qrpay_manage')->all(); // 合并且移除
                    $list[$key]['add_time'] = local_date('Y-m-d H:i', $value['add_time']);
                    $list[$key]['user_name'] = $this->get_user_name($value['pay_user_id'], $value['openid']);
                    $list[$key]['payment_code'] = $this->get_payment_name($value['payment_code']);
                    $list[$key]['pay_status'] = ($value['pay_status'] == 1) ? '已支付' : '未支付';
                    if ($this->wechat_ru_id > 0) {
                        $list[$key]['is_settlement'] = ($value['is_settlement'] == 1) ? '已结算' : '未结算';
                    }
                    $list[$key]['qrpay_type'] = isset($value['type']) && $value['type'] == 0 ? '自助收款码' : '指定金额收款码';
                    $list[$key]['tag_name'] = isset($value['tag_id']) && $value['tag_id'] > 0 ? $this->get_qrpay_tag($value['tag_id']) : '-';
                }
                $excel = new Spreadsheet();
                //设置单元格宽度
                $excel->getActiveSheet()->getDefaultColumnDimension()->setAutoSize(true);
                //设置表格的宽度  手动
                $excel->getActiveSheet()->getColumnDimension('B')->setWidth(20);
                $excel->getActiveSheet()->getColumnDimension('D')->setWidth(20);
                $excel->getActiveSheet()->getColumnDimension('G')->setWidth(20);
                $excel->getActiveSheet()->getColumnDimension('H')->setWidth(15);
                $excel->getActiveSheet()->getColumnDimension('I')->setWidth(15);
                $excel->getActiveSheet()->getColumnDimension('J')->setWidth(20);
                //设置标题
                if ($this->wechat_ru_id > 0) {
                    $rowVal = [
                        0 => 'id',
                        1 => '收款订单号',
                        2 => '收款金额(元)',
                        3 => '收款码类型',
                        4 => '标签',
                        5 => '用户',
                        6 => '支付方式',
                        7 => '支付状态',
                        8 => '结算状态',
                        9 => '收款时间'
                    ];
                } else {
                    $rowVal = [
                        0 => 'id',
                        1 => '收款订单号',
                        2 => '收款金额(元)',
                        3 => '收款码类型',
                        4 => '标签',
                        5 => '用户',
                        6 => '支付方式',
                        7 => '支付状态',
                        8 => '收款时间'
                    ];
                }
                foreach ($rowVal as $k => $r) {
                    $excel->getActiveSheet()->getStyleByColumnAndRow($k + 1, 1)->getFont()->setBold(true);//字体加粗
                    $excel->getActiveSheet()->getStyleByColumnAndRow($k + 1, 1)->getAlignment(); //文字居中
                    $excel->getActiveSheet()->setCellValueByColumnAndRow($k + 1, 1, $r);
                }
                //设置当前的sheet索引 用于后续内容操作
                $excel->setActiveSheetIndex(0);
                $objActSheet = $excel->getActiveSheet();
                //设置当前活动的sheet的名称
                $title = "收款码记录";
                $objActSheet->setTitle($title);
                //设置单元格内容
                foreach ($list as $k => $v) {
                    $num = $k + 2;
                    if ($this->wechat_ru_id > 0) {
                        $excel->setActiveSheetIndex(0)
                            //Excel的第A列，uid是你查出数组的键值，下面以此类推
                            ->setCellValue('A' . $num, $v['id'])
                            ->setCellValue('B' . $num, $v['pay_order_sn'])
                            ->setCellValue('C' . $num, $v['pay_amount'])
                            ->setCellValue('D' . $num, $v['qrpay_type'])
                            ->setCellValue('E' . $num, $v['tag_name'])
                            ->setCellValue('F' . $num, $v['user_name'])
                            ->setCellValue('G' . $num, $v['payment_code'])
                            ->setCellValue('H' . $num, $v['pay_status'])
                            ->setCellValue('I' . $num, $v['is_settlement'])
                            ->setCellValue('J' . $num, $v['add_time']);
                    } else {
                        $excel->setActiveSheetIndex(0)
                            //Excel的第A列，uid是你查出数组的键值，下面以此类推
                            ->setCellValue('A' . $num, $v['id'])
                            ->setCellValue('B' . $num, $v['pay_order_sn'])
                            ->setCellValue('C' . $num, $v['pay_amount'])
                            ->setCellValue('D' . $num, $v['qrpay_type'])
                            ->setCellValue('E' . $num, $v['tag_name'])
                            ->setCellValue('F' . $num, $v['user_name'])
                            ->setCellValue('G' . $num, $v['payment_code'])
                            ->setCellValue('H' . $num, $v['pay_status'])
                            ->setCellValue('I' . $num, $v['add_time']);
                    }
                }
                $name = date('Y-m-d'); //设置文件名
                header("Content-Type: application/force-download");
                header("Content-Type: application/octet-stream");
                header("Content-Type: application/download");
                header("Content-Transfer-Encoding:utf-8");
                header("Pragma: no-cache");
                header('Content-Type: application/vnd.ms-e xcel');
                header('Content-Disposition: attachment;filename="' . $title . '_' . urlencode($name) . '.xls"');
                header('Cache-Control: max-age=0');
                $objWriter = IOFactory::createWriter($excel, 'Xls');
                $objWriter->save('php://output');
                exit;
            } else {
                return $this->message('该时间段没有要导出的数据', null, 2, $this->wechat_ru_id);
            }
        }

        return $this->wechat_ru_id > 0 ? redirect()->route('seller/wechat/data_list', ['type' => $this->marketing_type, 'function' => 'qrpay_log_list']) : redirect()->route('admin/wechat/data_list', ['type' => $this->marketing_type, 'function' => 'qrpay_log_list']);
    }

    /**
     * 收款码优惠列表
     * @return
     */
    public function marketQrpayDiscounts()
    {
        // 编辑
        $handler = request()->input('handler', '');
        $function = request()->input('function', '');

        if ($handler && $handler == 'edit') {
            if (request()->isMethod('POST')) {
                $json_result = ['error' => 0, 'msg' => '', 'url' => '']; // 初始化通知信息

                $id = request()->input('id', 0);
                $data = request()->input('data');

                $wheredata = [
                    'min_amount' => $data['min_amount'],
                    'discount_amount' => $data['discount_amount'],
                    'max_discount_amount' => $data['max_discount_amount'],
                ];
                // 更新
                if ($id) {
                    QrpayDiscounts::where(['id' => $id, 'ru_id' => $this->wechat_ru_id])->update($wheredata);
                    $json_result = [
                        'error' => 0,
                        'msg' => L('wechat_editor') . L('success'),
                        'url' => $this->wechat_ru_id > 0 ? route('seller/wechat/data_list', ['type' => $this->marketing_type, 'function' => $function]) : route('admin/wechat/data_list', ['type' => $this->marketing_type, 'function' => $function])
                    ];
                    return response()->json($json_result);
                } else {
                    $wheredata['add_time'] = $this->timeRepository->getGmTime();
                    $wheredata['ru_id'] = $this->wechat_ru_id;
                    $wheredata['status'] = 1;
                    QrpayDiscounts::insert($wheredata);
                    $json_result = [
                        'error' => 0,
                        'msg' => L('add') . L('success'),
                        'url' => $this->wechat_ru_id > 0 ? route('seller/wechat/data_list', ['type' => $this->marketing_type, 'function' => $function]) : route('admin/wechat/data_list', ['type' => $this->marketing_type, 'function' => $function])
                    ];
                    return response()->json($json_result);
                }
            }

            // 显示编辑页面
            $id = request()->input('id', 0);
            $info = QrpayDiscounts::where(['id' => $id, 'ru_id' => $this->wechat_ru_id])->first();
            $info = $info ? $info->toArray() : [];

            if (!empty($id)) {
                if (empty($info)) {
                    return $this->message('数据不存在', $this->wechat_ru_id > 0 ? route('seller/wechat/data_list', ['type' => $this->marketing_type, 'function' => $function]) : route('admin/wechat/data_list', ['type' => $this->marketing_type, 'function' => $function]), 2, $this->wechat_ru_id);
                }
            }
            $info['ru_id'] = $this->wechat_ru_id;
            $this->plugin_assign('info', $info);
            return $this->plugin_display('market_discounts_edit', $this->_data);
        }

        // 优惠列表
        $filter['type'] = $this->marketing_type;
        $filter['function'] = $function;
        $offset = $this->pageLimit($this->wechat_ru_id > 0 ? route('seller/wechat/data_list', $filter) : route('admin/wechat/data_list', $filter), $this->page_num);

        $model = QrpayDiscounts::where(['ru_id' => $this->wechat_ru_id]);

        $total = $model->count();

        $list = $model->offset($offset['start'])
            ->limit($offset['limit'])
            ->orderBy('id', 'desc')
            ->orderBy('add_time', 'desc')
            ->get();
        $list = $list ? $list->toArray() : [];

        foreach ($list as $key => $value) {
            $list[$key]['status_fromat'] = $value['status'] == 1 ? '正在进行' : '已失效';
            $list[$key]['dis_name'] = "满" . $value['min_amount'] . "减" . $value['discount_amount'];
        }

        // 新增的条件
        $disabled_num = QrpayDiscounts::where(['status' => 1, 'ru_id' => $this->wechat_ru_id])->count();
        $this->plugin_assign('disabled_num', $disabled_num);

        $this->plugin_assign('page', $this->pageShow($total));
        $this->plugin_assign('list', $list);
        return $this->plugin_display('market_discounts', $this->_data);
    }

    /**
     * 收款码标签列表
     * @return
     */
    public function marketQrpayTagList()
    {
        // 编辑
        $handler = request()->input('handler', '');
        $function = request()->input('function', '');

        if ($handler && $handler == 'edit') {
            if (request()->isMethod('POST')) {
                $json_result = ['error' => 0, 'msg' => '', 'url' => '']; // 初始化通知信息

                $id = request()->input('id', 0);
                $data = request()->input('data');

                $wheredata = [
                    'tag_name' => $data['tag_name'],
                ];
                // 更新
                if ($id) {
                    QrpayTag::where(['id' => $id, 'ru_id' => $this->wechat_ru_id])->update($wheredata);
                    $json_result = [
                        'error' => 0,
                        'msg' => L('wechat_editor') . L('success'),
                        'url' => $this->wechat_ru_id > 0 ? route('seller/wechat/data_list', ['type' => $this->marketing_type, 'function' => $function]) : route('admin/wechat/data_list', ['type' => $this->marketing_type, 'function' => $function])
                    ];
                    return response()->json($json_result);
                } else {
                    $wheredata['add_time'] = $this->timeRepository->getGmTime();
                    $wheredata['ru_id'] = $this->wechat_ru_id;
                    QrpayTag::insert($data);
                    $json_result = [
                        'error' => 0,
                        'msg' => L('add') . L('success'),
                        'url' => $this->wechat_ru_id > 0 ? route('seller/wechat/data_list', ['type' => $this->marketing_type, 'function' => $function]) : route('admin/wechat/data_list', ['type' => $this->marketing_type, 'function' => $function])
                    ];
                    return response()->json($json_result);
                }
            }
            // 显示
            $id = request()->input('id', 0);
            $info = QrpayTag::select('id', 'tag_name')->where(['id' => $id, 'ru_id' => $this->wechat_ru_id])->first();
            $info = $info ? $info->toArray() : [];

            if (!empty($id)) {
                if (empty($info)) {
                    return $this->message('数据不存在', $this->wechat_ru_id > 0 ? route('seller/wechat/data_list', ['type' => $this->marketing_type, 'function' => $function]) : route('admin/wechat/data_list', ['type' => $this->marketing_type, 'function' => $function]), 2, $this->wechat_ru_id);
                }
            }
            $info['ru_id'] = $this->wechat_ru_id;
            $this->plugin_assign('info', $info);
            return $this->plugin_display('market_tag_edit', $this->_data);
        }

        $filter['type'] = $this->marketing_type;
        $filter['function'] = $function;
        $offset = $this->pageLimit($this->wechat_ru_id > 0 ? route('seller/wechat/data_list', $filter) : route('admin/wechat/data_list', $filter), $this->page_num);

        $model = QrpayTag::where(['ru_id' => $this->wechat_ru_id]);

        $total = $model->count();
        // 列表
        $list = $model->select('id', 'tag_name', 'add_time')
            ->offset($offset['start'])
            ->limit($offset['limit'])
            ->orderBy('add_time', 'desc')
            ->orderBy('id', 'desc')
            ->get();
        $list = $list ? $list->toArray() : [];

        foreach ($list as $key => $value) {
            $list[$key]['add_time'] = local_date('Y-m-d H:i:s', $value['add_time']);
            $list[$key]['self_qrpay_num'] = $this->get_self_qrpay_num($value['id']);
            $list[$key]['fixed_qrpay_num'] = $this->get_fixed_qrpay_num($value['id']);
        }
        $this->plugin_assign('page', $this->pageShow($total));
        $this->plugin_assign('list', $list);
        return $this->plugin_display('market_tag_list', $this->_data);
    }

    /**
     * 行为操作
     * @param handler 例如 删除
     */
    public function executeAction()
    {
        if (request()->isMethod('POST')) {
            $json_result = ['error' => 0, 'msg' => '', 'url' => ''];

            $handler = request()->input('handler', '');

            // 删除收款码
            if ($handler && $handler == 'qr_delete') {
                $id = request()->input('id', 0);
                if (!empty($id)) {
                    QrpayManage::where(['id' => $id, 'ru_id' => $this->wechat_ru_id])->delete();
                    $json_result['msg'] = '删除成功！';
                    return response()->json($json_result);
                } else {
                    $json_result['msg'] = '删除失败！';
                    return response()->json($json_result);
                }
            }

            // 使优惠活动失效
            if ($handler && $handler == 'disabled') {
                $id = request()->input('id', 0);
                if (!empty($id)) {
                    QrpayDiscounts::where(['id' => $id, 'ru_id' => $this->wechat_ru_id])->update(['status' => 0]);
                    return response()->json($json_result);
                }
            }

            // 删除标签
            if ($handler && $handler == 'tag_delete') {
                $id = request()->input('id', 0);
                if (!empty($id)) {
                    QrpayLog::where(['id' => $id, 'ru_id' => $this->wechat_ru_id])->delete();
                    $json_result['msg'] = '删除成功！';
                    return response()->json($json_result);
                } else {
                    $json_result['msg'] = '删除失败！';
                    return response()->json($json_result);
                }
            }

            // 删除收款记录
            if ($handler && $handler == 'log_delete') {
                $log_id = request()->input('log_id', 0);
                if (!empty($log_id)) {
                    QrpayLog::where(['id' => $log_id, 'ru_id' => $this->wechat_ru_id])->delete();
                    $json_result['msg'] = '删除成功！';
                    return response()->json($json_result);
                } else {
                    $json_result['msg'] = '删除失败！';
                    return response()->json($json_result);
                }
            }

            // 手动结算
            if ($handler && $handler == 'is_settlement') {
                $log_id = request()->input('log_id', 0);
                if (!empty($log_id)) {
                    $re = app(QrpayService::class)->insert_seller_account_log($log_id);
                    $json_result['msg'] = $re == true ? '结算成功！' : '结算失败！';
                    return response()->json($json_result);
                } else {
                    $json_result['msg'] = '结算失败！';
                    return response()->json($json_result);
                }
            }
        }
    }

    /**
     * 查询收款码标签
     * @param  [int] $id
     * @return
     */
    public function get_qrpay_tag($id = 0)
    {
        if ($id > 0) {
            return QrpayTag::where(['id' => $id, 'ru_id' => $this->wechat_ru_id])->value('tag_name');
        }

        $list = QrpayTag::select('id', 'tag_name')
            ->where(['ru_id' => $this->wechat_ru_id])
            ->orderBy('id', 'desc')
            ->orderBy('add_time', 'desc')
            ->get();
        return $list ? $list->toArray() : [];
    }

    /**
     * 查询收款码优惠
     * @return
     */
    public function get_qrpay_discounts($id = 0)
    {
        if ($id > 0) {
            $res = QrpayDiscounts::select('min_amount', 'discount_amount', 'max_discount_amount')
                ->where(['status' => 1, 'id' => $id, 'ru_id' => $this->wechat_ru_id])
                ->first();
            $res = $res ? $res->toArray() : [];
            if (empty($res)) {
                return '已失效';
            }
            return $res['dis_name'] = "满" . $res['min_amount'] . "减" . $res['discount_amount'];
        }

        $list = QrpayDiscounts::select('id', 'min_amount', 'discount_amount', 'max_discount_amount')
            ->where(['ru_id' => $this->wechat_ru_id])
            ->where(['status' => 1, 'ru_id' => $this->wechat_ru_id])
            ->get();
        $list = $list ? $list->toArray() : [];

        foreach ($list as $key => $value) {
            $list[$key]['dis_name'] = "满" . $value['min_amount'] . "减" . $value['discount_amount'];
        }
        return $list;
    }

    /**
     * 相关自助收款码数量
     * @param integer $tag_id
     * @return
     */
    public function get_self_qrpay_num($tag_id = 0)
    {
        $num = QrpayManage::where(['ru_id' => $this->wechat_ru_id, 'tag_id' => $tag_id, 'type' => 0])->count();
        return $num;
    }

    /**
     * 相关指定金额收款码数量
     * @param integer $tag_id
     * @return
     */
    public function get_fixed_qrpay_num($tag_id = 0)
    {
        $num = QrpayManage::where(['ru_id' => $this->wechat_ru_id, 'tag_id' => $tag_id, 'type' => 1])->count();
        return $num;
    }

    /**
     * 查询用户名昵称
     * @param  $user_id
     * @param  $openid
     * @return
     */
    public function get_user_name($user_id, $openid = '')
    {
        if (!empty($openid)) {
            $users = Users::from('users as u')
                ->leftjoin('wechat_user as w', 'w.ect_uid', '=', 'u.user_id')
                ->select('u.user_name', 'w.nickname')
                ->where(['openid' => $openid])
                ->first();
        } else {
            $users = Users::select('user_name', 'nick_name as nickname')->where(['user_id' => $user_id])->first();
        }

        $users = $users ? $users->toArray() : [];

        if ($users) {
            $user_name = !empty($users['nickname']) ? $users['nickname'] : $users['user_name'];
        } else {
            $user_name = '匿名用户';
        }

        return $user_name;
    }

    /**
     * 查询支付方式名
     * @param  $code
     * @return
     */
    public function get_payment_name($code)
    {
        return Payment::where(['pay_code' => $code])->value('pay_name');
    }

    /**
     * 查询收款码名称
     * @param  $qrpay_id
     * @return
     */
    public function get_qrpay_name($qrpay_id)
    {
        return QrpayManage::where(['id' => $qrpay_id, 'ru_id' => $this->wechat_ru_id])->value('qrpay_name');
    }
}
