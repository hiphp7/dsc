# 分销商提现申请

#### 接口描述：

- 分销商提现申请（distribute/deposit_apply）

#### 接口版本：

|版本号|制定人|制定日期|修订日期|
|:----|:-----|:-----| ---- |
|1.0.0 | yangruopeng  |2019-07-02 |  2019-07-02 |

#### 请求URL:

- http://domain/api/distribute/deposit_apply

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
|  amount | 是 | 0 | Number | 提现金额 |
|  deposit_type | 是 | 0 | Number | 提现类型 0 线下付款, 1 微信企业付款至零钱, 2 微信企业付款至银行卡 |

#### 返回示例:

**正确时返回:**

```
{
    "status": "success",
    "data": {
        true
    }

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