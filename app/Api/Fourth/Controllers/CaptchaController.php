<?php

namespace App\Api\Fourth\Controllers;

use App\Api\Foundation\Controllers\Controller;
use Exception;
use Gregwar\Captcha\CaptchaBuilder;
use Gregwar\Captcha\PhraseBuilder;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Ramsey\Uuid\Uuid;

class CaptchaController extends Controller
{
    /**
     * @return JsonResponse
     * @throws Exception
     */
    public function index()
    {
        // 生成验证码图片的Builder对象
        $phraseBuilder = new PhraseBuilder(4, '0123456789');
        $builder = new CaptchaBuilder(null, $phraseBuilder);

        // 可以设置图片宽高
        $builder->build(100, 40);

        // 获取验证码的内容
        $phrase = $builder->getPhrase();

        // 把内容存入cache
        $client_id = Uuid::uuid1()->toString();
        Cache::put($client_id, $phrase, Carbon::now()->addMinutes(10));

        // 组装数据
        $data = [
            'client' => $client_id,
            'captcha' => $builder->inline()
        ];

        // 测试数据
        if (config('app.debug')) {
            $data['phrase'] = $phrase;
        }

        return $this->succeed($data);
    }
}
