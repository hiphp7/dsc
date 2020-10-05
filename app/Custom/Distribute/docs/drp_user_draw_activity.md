# 领取分销商活动

#### 接口描述：

- 分销商领取分销商活动（/user_draw_activity）

#### 接口版本：

|版本号|制定人|制定日期|修订日期|
|:----|:-----|:-----| ---- |
|1.0.0 | chenjianxing  |2019-06-20 |  2019-06-20 |

#### 请求URL:

- http://domain/api/distribute/user_draw_activity

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
| activity_id | 是 | 0  | int  | 活动ID |



#### 返回示例:

**正确时返回:**

```
{
    "status": "success",
    "data": {
        "code": 0,
        "msg": "领取成功"
    },
    "time": 1561020670
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