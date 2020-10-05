# 获取当前所有分销商活动

#### 接口描述：

- 获取当前所有已开启分销商活动（/all_activity）

#### 接口版本：

|版本号|制定人|制定日期|修订日期|
|:----|:-----|:-----| ---- |
|1.0.0 | chenjianxing  |2019-06-20 |  2019-06-20 |
|1.0.1 | chenjianxing  |2019-06-27 |  2019-06-27 |

#### 请求URL:

- http://domain/api/distribute/all_activity

#### 请求方式：

- <font color=red>POST</font>

#### 请求头：

|参数名|是否必须|类型|说明|
|:----  |:---|:-----|-----|
|Content-Type | 是  |String |请求类型： application/json   |
| token | 是  |String | 请求内容签名    |


#### 请求参数:

|参数名|是否必须|默认值|类型|说明|
| :----- | :--- | :-----  | -----  | ----- |
| activity_type | 是 | 0  | int  | 活动类型(0 所有活动 1 未领取 2 已领取活动 3 已过期活动  4  未过期活动) |



#### 返回示例:

**正确时返回:**

```
{
    "status": "success",
    "data": [
        {
            "id": 1,
            "act_name": "测试活动",
            "act_dsc": "测试展示",
            "start_time": 1560849273,
            "end_time": 1561713391,
            "text_info": 20,
            "is_finish": 1,
            "raward_money": 0,
            "raward_type": 0,
            "add_time": 1560386215,
            "goods_id": 635,
            "goods": {
                "goods_id": 635,
                "goods_name": "韩都衣舍2017韩版女装新款黑白拼接插肩棒球服春季短外套HH5597妠 朴信惠同款 黑白拼接 插肩袖 棒球服",
                "goods_img": "images/201703/goods_img/0_G_1489102950448.jpg"
            },
            "start_time_format": "2019-06-19 01:14:33",
            "end_time_format": "2019-06-29 01:16:31",
            "add_time_format": "2019-06-13 16:36:55",
            "is_finish_format": "已开启",
            "raward_type_format": "奖励积分",
            "act_type_format": "分享点击次数",
            "award_status": "未完成"
        },
    "time": 1561002786
}
```

**错误时返回:**


```
{
    "status": 'failed',
    "errors": {
        "code": 404,
        "message": "message"
    },
    "time": 1553515084
}
```

#### 返回参数说明:

|参数名|类型|说明|
|:-----  |:-----|----- |
|  id | int | 活动ID |
|  act_name | string | 活动名称 |
|  act_dsc | string | 活动介绍 |
|  start_time | int | 开始时间 |
|  end_time | int | 结束时间 |
|  text_info | int | 活动条件要求值 |
|  is_finish | int | 活动是否开启 |
|  raward_money | int | 活动奖励额度 |
|  raward_type | int | 活动奖励类型  0  奖励积分  1  奖励余额 |
|  add_time | int | 添加时间 |
|  goods_id | int | 参与活动的商品ID |
|  act_type_share | int | 分享点击量要求 |
|  act_type_place | int | 分享下单量要求 |
|  goods | List[] | 参与活动的商品信息 |
|  ├─ goods_id | int | 商品ID |
|  ├─ goods_name | str | 商品名称 |
|  ├─ goods_img | str | 商品图片 |
|  past_due | int | 活动是否过期 0 未过期 1 已过期 |
|  past_due_format | str | 活动是否过期格式化显示 |
|  start_time_format | str | 格式化活动开始时间 |
|  end_time_format | str | 格式化活动结束时间 |
|  add_time_format | str | 格式化添加时间 |
|  is_finish_format | str | 活动是否开启 |
|  raward_type_format | str | 格式化奖励类型 |
|  act_type_format | str | 格式化活动条件 |
|  award_status | str | 活动完成状态 |
|  draw_status | str | 活动领取状态(暂不用) |
|  completeness_share | int | 分享完成量 |
|  completeness_place | int | 下单完成量 |


#### 备注:

- 更多返回错误代码请看首页的错误代码描述