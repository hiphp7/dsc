# 获取分销条件信息

#### 接口描述：

- 获取分销条件信息（/drp_user_upgrade_condition）

#### 接口版本：

|版本号|制定人|制定日期|修订日期|
|:----|:-----|:-----| ---- |
|1.0.0 | chenjianxing  |2019-06-26 |  2019-06-26 |

#### 请求URL:

- http://domain/api/distribute/drp_user_upgrade_condition

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
| condition_status | 是 | 0  | int  | 条件类型(0 未完成条件  1 已完成条件) |



#### 返回示例:

**正确时返回:**

```
{
    "status": "success",
    "data": [
        {
            "name": "分销订单总金额",
            "value": "21",
            "type": "积分",
            "award_num": 100
        }
    ],
    "time": 1561545264
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
|  name | string | 条件要求内容 |
|  value | int | 条件要求值 |
|  type | string | 条件达成奖励类型 |
|  award_num | int | 条件达成奖励额度 |


#### 备注:

- 更多返回错误代码请看首页的错误代码描述