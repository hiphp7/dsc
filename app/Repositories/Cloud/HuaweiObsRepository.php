<?php

namespace App\Repositories\Cloud;

use App\Kernel\Repositories\Cloud\HuaweiObsRepository as Base;

/**
 * Class HuaweiObsRepository
 * @method cloudUpload(array $data = []) 上传OBS图片
 * @method cloudDelete(array $data = []) 删除OBS图片
 * @method cloudList(array $data = []) 获取OBS指定信息
 * @method bucketInfo() 获取OBS Bucket信息
 * @package App\Repositories\Cloud
 */
class HuaweiObsRepository extends Base
{
}
