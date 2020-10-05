<?php

namespace App\Api\Fourth\Controllers;

use App\Api\Foundation\Controllers\Controller;
use App\Models\ShopConfig;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Class MaterialController
 * @package App\Api\Fourth\Controllers
 */
class MaterialController extends Controller
{
    protected $config;

    public function __construct()
    {
        /* 商城配置信息 */
        $shopConfig = cache('shop_config');
        if (is_null($shopConfig)) {
            $this->config = app(\App\Services\Common\ConfigService::class)->getConfig();
        } else {
            $this->config = $shopConfig;
        }
    }

    /**
     * 保存素材图片
     * @param Request $request
     * @return JsonResponse
     * @throws Exception
     */
    public function uploads(Request $request)
    {
        $uid = $this->authorization();

        if ($uid > 0) {

            $disk = 'public';
            if (isset($this->config['open_oss']) && $this->config['open_oss'] == 1) {

                if (!isset($this->config['cloud_storage'])) {
                    $cloud_storage = ShopConfig::where('code', 'cloud_storage')->value('value');
                    $cloud_storage = $cloud_storage ? $cloud_storage : 0;
                } else {
                    $cloud_storage = $this->config['cloud_storage'];
                }

                if ($cloud_storage == 1) {
                    $disk = 'obs';
                } else {
                    $disk = 'oss';
                }
            }

            /**
             * 功能：文件上传，支持file文件和base64字符串上传
             * file类型：支持单一文件上传
             * base64类型：支持多文件上传
             */
            $urls = [];
            if ($request->hasFile('file')) {
                $path = $request->file('file')->store('uploads', $disk);

                $url = Storage::disk($disk)->url($path);

                array_push($urls, $url);
            } else {
                // 接收前端图片 base64 内容
                $files = $request->get('file');

                $items = (count($files) > 1) ? $files : [$files];

                foreach ($items as $item) {
                    // 保存到存储
                    $content = $item['content'];
                    if (preg_match('/^(data:\s*image\/(\w+);base64,)/', $content, $matches)) {
                        $content = base64_decode(str_replace($matches[1], '', $content));

                        // 定义 path 文件
                        $path = 'uploads/' . $uid . '_' . uniqid() . '.' . $matches[2];

                        // 保存文件
                        Storage::disk($disk)->put($path, $content);

                        // 返回 url 地址
                        $url = Storage::disk($disk)->url($path);

                        array_push($urls, $url);
                    }
                }
            }

            return $this->succeed($urls);
        }

        return $this->failed('permission denied');
    }
}
