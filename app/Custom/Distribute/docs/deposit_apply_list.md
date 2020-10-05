# 分销商的提现申请记录

#### 接口描述：

- 分销商的提现申请记录（distribute/deposit_apply_list）

#### 接口版本：

|版本号|制定人|制定日期|修订日期|
|:----|:-----|:-----| ---- |
|1.0.0 | yangruopeng  |2019-07-02 |  2019-07-02 |

#### 请求URL:

- http://domain/api/distribute/deposit_apply_list

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
|  page | 是 | 0 | Number | 分页 |
|  size | 是 | 0 | Number | 每页数量 |
|  deposit_status | 否 | 0 | Number | 提现类型 默认 -1 全部, 0 未提现, 1 已提现 |

#### 返回示例:

**正确时返回:**

```
{
    "status": "success",
    "data": {
        "list": [
            {
                "id": 5,
                "user_id": 60,
                "money": "2.00",
                "add_time": 1560377422,
                "check_status": 1,
                "deposit_type": 2,
                "deposit_status": 1,
                "bank_info": {
                    "enc_bank_no": "11111111",
                    "enc_true_name": "tttt",
                    "bank_code": "1005"
                },
                "trade_no": "2019061314198249302",
                "deposit_data": {
                    "return_code": "SUCCESS",
                    "result_code": "SUCCESS",
                    "status": "SUCCESS"
                },
                "finish_status": 1,
                "deposit_fee": "1.00",
                "drp_shop": {
                    "user_id": 60,
                    "shop_name": "test分销"
                },
                "shop_name": "test分销",
                "add_time_format": "2019-06-13 14:10:22",
                "check_status_format": "已审核",
                "deposit_type_format": "微信企业付款至银行卡",
                "deposit_status_format": "已提现",
                "finish_status_format": "已到账"
            }
        ],
        "total": 3
    },
    "time": 1562045462
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



#### 备注:

- 更多返回错误代码请看首页的错误代码描述