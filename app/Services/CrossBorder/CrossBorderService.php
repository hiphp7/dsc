<?php

namespace App\Services\CrossBorder;

/**
 *
 *
 * Class CrossBorderService
 * @package App\Services\CrossBorder
 */
class CrossBorderService
{
    /*
    * 跨境服务类是否存在 存在则实例化
    */
    public function cbecExists()
    {
        $cbec = 'App\\Custom\\CrossBorder\\Services\\CbecService';

        if (class_exists($cbec)) {
            return app($cbec);
        } else {
            return null;
        }
    }

    /*
    * 类是否存在 存在则实例化
    */
    public function webExists()
    {
        $web = 'App\\Custom\\CrossBorder\\Controllers\\WebController';

        if (class_exists($web)) {
            return app($web);
        } else {
            return null;
        }
    }

    /*
    * 类是否存在 存在则实例化
    */
    public function adminExists()
    {
        $admin = 'App\\Custom\\CrossBorder\\Controllers\\AdminController';

        if (class_exists($admin)) {
            return app($admin);
        } else {
            return null;
        }
    }

    /*
    * 类是否存在 存在则实例化
    */
    public function sellerExists()
    {
        $seller = 'App\\Custom\\CrossBorder\\Controllers\\SellerController';

        if (class_exists($seller)) {
            return app($seller);
        } else {
            return null;
        }
    }
}
