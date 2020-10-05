<?php

namespace App\Services\PrivAdmin;

/**
 * 商城权限
 * Class CrowdFund
 * @package App\Services
 */
class PrivAdminService
{
    public $priv_id = 0;


    /**
     * 构造函数
     *
     * @access  public
     * @param string $tpl
     * @return  void
     */
    public function __construct($array = [])
    {
        $this->priv_id = isset($array['priv_id']) && !empty($array['priv_id']) ? intval($array['priv_id']) : 0;
    }
}
