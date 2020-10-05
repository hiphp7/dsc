<?php

namespace App\Repositories\Cloud;

use App\Kernel\Repositories\Cloud\AliOssRepository as Base;

/**
 * Class AliOssRepository
 * @method cloudUpload(array $data = []) 上传OBS图片
 * @method cloudDelete(array $data = []) 删除OBS图片
 * @method cloudList(array $data = []) 获取OBS指定信息
 * @method bucketInfo() 获取OBS Bucket信息
 * @package App\Repositories\Cloud
 */
class AliOssRepository extends Base
{

}
