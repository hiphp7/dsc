<?php

namespace App\Services\Integrate;

use App\Models\ShopConfig;
use App\Repositories\Common\BaseRepository;


class IntegrateManageService
{
    protected $baseRepository;

    public function __construct(
        BaseRepository $baseRepository
    )
    {
        $this->baseRepository = $baseRepository;
    }

    /**
     * @param $code
     * @param $cfg
     * @return bool
     * @throws \Exception
     */
    public function saveIntegrateConfig($code, $cfg)
    {
        $res = ShopConfig::where('code', 'integrate_code')->count();
        if ($res < 1) {
            $data = [
                'code' => 'integrate_code',
                'value' => $code
            ];
            ShopConfig::insert($data);
        } else {
            $value = ShopConfig::where('code', 'integrate_code')->value('value');
            $value = $value ? $value : '';
            if ($code != $value) {
                /* 有缺换整合插件，需要把积分设置也清除 */
                $data = ['value' => ''];
                ShopConfig::where('code', 'points_rule')->update($data);
            }
            $data = ['value' => $code];
            ShopConfig::where('code', 'integrate_code')->update($data);
        }

        /* 当前的域名 */
        if (request()->server('HTTP_X_FORWARDED_HOST')) {
            $cur_domain = request()->server('HTTP_X_FORWARDED_HOST');
        } elseif (request()->server('HTTP_HOST')) {
            $cur_domain = request()->server('HTTP_HOST');
        } else {
            if (request()->server('SERVER_NAME')) {
                $cur_domain = request()->server('SERVER_NAME');
            } elseif (request()->server('SERVER_ADDR')) {
                $cur_domain = request()->server('SERVER_ADDR');
            }
        }

        /* 整合对象的域名 */
        $integrate_url = isset($cfg['integrate_url']) ? $cfg['integrate_url'] : '';
        $int_domain = str_replace(['http://', 'https://'], '', $integrate_url);
        if (strrpos($int_domain, '/')) {
            $int_domain = substr($int_domain, 0, strrpos($int_domain, '/'));
        }

        if ($cur_domain != $int_domain) {
            $same_domain = true;
            $domain = '';

            /* 域名不一样，检查是否在同一域下 */
            $cur_domain_arr = explode(".", $cur_domain);
            $int_domain_arr = explode(".", $int_domain);

            if (count($cur_domain_arr) != count($int_domain_arr) || $cur_domain_arr[0] == '' || $int_domain_arr[0] == '') {
                /* 域名结构不相同 */
                $same_domain = false;
            } else {
                /* 域名结构一致，检查除第一节以外的其他部分是否相同 */
                $count = count($cur_domain_arr);

                for ($i = 1; $i < $count; $i++) {
                    if ($cur_domain_arr[$i] != $int_domain_arr[$i]) {
                        $domain = '';
                        $same_domain = false;
                        break;
                    } else {
                        $domain .= ".$cur_domain_arr[$i]";
                    }
                }
            }

            if ($same_domain == false) {
                /* 不在同一域，设置提示信息 */
                $cfg['cookie_domain'] = '';
                $cfg['cookie_path'] = '/';
            } else {
                $cfg['cookie_domain'] = $domain;
                $cfg['cookie_path'] = '/';
            }
        } else {
            $cfg['cookie_domain'] = '';
            $cfg['cookie_path'] = '/';
        }

        $res = ShopConfig::where('code', 'integrate_config')->count();
        if ($res < 1) {
            $data = [
                'code' => 'integrate_config',
                'value' => serialize($cfg)
            ];
            ShopConfig::insert($data);
        } else {
            $data = ['value' => serialize($cfg)];
            ShopConfig::where('code', 'integrate_config')->update($data);
        }

        $this->baseRepository->getCacheForgetlist(['shop_config']);

        return true;
    }
}
