<?php

namespace App\Repositories\Common;

use App\Kernel\Repositories\Common\FileSystemsRepository as Base;

/**
 * Class FileSystemsRepository
 * @method fileExists($file = '') 判断路径的文件是否存在
 * @method dirExists($path = '', $make = 0) 判断路径的目录是否存在
 * @package App\Repositories\Common
 */
class FileSystemsRepository extends Base
{

}
