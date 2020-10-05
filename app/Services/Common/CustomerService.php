<?php

namespace App\Services\Common;

use App\Models\ImConfigure;
use App\Models\ImDialog;
use App\Models\ImMessage;
use App\Models\ImService;

/**
 * Class CustomerService
 * @package App\Services\Common
 */
class CustomerService
{
    /**
     * @var ImConfigure
     */
    protected $configure;

    /**
     * @var ImDialog
     */
    protected $dialog;

    /**
     * @var ImMessage
     */
    protected $message;

    /**
     * @var ImService
     */
    protected $service;

    /**
     * CustomerService constructor.
     * @param ImConfigure $configure
     * @param ImDialog $dialog
     * @param ImMessage $message
     * @param ImService $service
     */
    public function __construct(
        ImConfigure $configure,
        ImDialog $dialog,
        ImMessage $message,
        ImService $service
    )
    {
        $this->configure = $configure;
        $this->dialog = $dialog;
        $this->message = $message;
        $this->service = $service;
    }

    /**
     * 获取客服配置
     * @param $ser_id 客服ID
     * @return mixed
     */
    public function setting($ser_id)
    {
        return $this->configure->where('ser_id', $ser_id)->select();
    }

    /**
     * 创建客服配置
     * @param $ser_id 客服ID
     * @param $type 配置类型
     * @param $content 配置内容
     * @param $is_on 是否启用
     * @return mixed
     */
    public function createSetting($ser_id, $type, $content, $is_on)
    {
        return $this->configure->create([
            'ser_id' => $ser_id,
            'type' => $type,
            'content' => $content,
            'is_on' => $is_on,
        ]);
    }

    /**
     * 保存客服配置
     * @param $id 主键
     * @param $ser_id 客服ID
     * @param $type 配置类型
     * @param $content 配置内容
     * @param $is_on 是否启用
     * @return bool
     */
    public function saveSetting($id, $ser_id, $type, $content, $is_on)
    {
        return $this->configure->where('id', $id)->save([
            'ser_id' => $ser_id,
            'type' => $type,
            'content' => $content,
            'is_on' => $is_on,
        ]);
    }

    /**
     * 删除客服配置
     * @param $id 配置ID
     * @param $ser_id 客服ID
     * @return mixed
     */
    public function deleteSetting($id, $ser_id)
    {
        return $this->configure->where('ser_id', $ser_id)->delete($id);
    }

    /**
     * 获取会话列表
     * @param $attr_id 属性ID（customer_id 或 services_id）
     * @param $type 类型（customer 或 services）
     * @return mixed
     */
    public function dialog($attr_id, $type)
    {
        return $this->dialog->where($type . '_id', $attr_id)->select();
    }

    /**
     * 创建会话
     * @param $cid 客户ID
     * @param $sid 客服ID
     * @param $gid 商品ID
     * @param $shop_id 店铺ID
     * @param $st 开始时间
     * @param $et 结束时间
     * @param $origin 消息来源
     * @param $status 状态
     * @return mixed
     */
    public function createDialog($cid, $sid, $gid, $shop_id, $st, $et, $origin, $status)
    {
        return $this->dialog->create([
            'customer_id' => $cid,
            'services_id' => $sid,
            'goods_id' => $gid,
            'store_id' => $shop_id,
            'start_time' => $st,
            'end_time' => $et,
            'origin' => $origin,
            'status' => $status,
        ]);
    }

    /**
     * 保存会话
     * @param $id 主键
     * @param $cid 客户ID
     * @param $sid 客服ID
     * @param $gid 商品ID
     * @param $shop_id 店铺ID
     * @param $st 开始时间
     * @param $et 结束时间
     * @param $origin 消息来源
     * @param $status 状态
     * @return mixed
     */
    public function saveDialog($id, $cid, $sid, $gid, $shop_id, $st, $et, $origin, $status)
    {
        return $this->dialog->where('id', $id)->save([
            'customer_id' => $cid,
            'services_id' => $sid,
            'goods_id' => $gid,
            'store_id' => $shop_id,
            'start_time' => $st,
            'end_time' => $et,
            'origin' => $origin,
            'status' => $status,
        ]);
    }

    /**
     * 删除会话（一般不使用！！！）
     * @param $id
     * @return bool|null
     * @throws \Exception
     */
    public function deleteDialog($id)
    {
        return $this->dialog->delete($id);
    }

}
