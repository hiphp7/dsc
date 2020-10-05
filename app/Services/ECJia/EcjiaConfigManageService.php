<?php

namespace App\Services\ECJia;


use App\Models\ShopConfig;
use App\Repositories\Common\BaseRepository;

/**
 * 分销
 * Class DrpService
 * @package App\Services\ECJia
 */
class EcjiaConfigManageService
{
    protected $baseRepository;

    public function __construct(
        BaseRepository $baseRepository
    )
    {

        $this->baseRepository = $baseRepository;
    }

    // ecjia config
    public function ecjiaConfig($code)
    {
        $value = ShopConfig::where('code', $code)->value('value');
        $value = $value ?? '';
        return $value;
    }

    // ecjia config
    public function updateConfig($code, $value)
    {
        $data = ['value' => $value];
        ShopConfig::where('code', $code)->update($data);
    }

}
