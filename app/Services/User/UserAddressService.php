<?php

namespace App\Services\User;


use App\Models\GroupbuyOrder;
use App\Models\OrderInfo;
use App\Models\OrderReturn;
use App\Models\Region;
use App\Models\UserAddress;
use App\Models\Users;
use App\Models\WholesaleOrderInfo;
use App\Repositories\Common\BaseRepository;

class UserAddressService
{
    protected $baseRepository;

    public function __construct(
        BaseRepository $baseRepository
    )
    {
        $this->baseRepository = $baseRepository;
    }

    /**
     * 获取会员收货地址信息
     *
     * @access  public
     * @param int $address_id
     * @param int $user_id
     * @return array
     */
    public function getUserAddressInfo($address_id = 0, $user_id = 0)
    {
        if ($address_id > 0) {
            $consignee = UserAddress::where('user_id', $user_id)->where('address_id', $address_id);
        } else {
            $address_id = Users::where('user_id', $user_id)->value('address_id');

            $consignee = UserAddress::where('user_id', $user_id)->where('address_id', $address_id);
            $consignee = $consignee->whereHas('getUsers');
        }

        $consignee = $consignee->with([
            'getRegionProvince' => function ($query) {
                $query->select('region_id', 'region_name');
            },
            'getRegionCity' => function ($query) {
                $query->select('region_id', 'region_name');
            },
            'getRegionDistrict' => function ($query) {
                $query->select('region_id', 'region_name');
            },
            'getRegionStreet' => function ($query) {
                $query->select('region_id', 'region_name');
            }
        ]);

        $consignee = $this->baseRepository->getToArrayFirst($consignee);

        if ($consignee) {
            if ($consignee['get_region_province']) {
                $province_name = $consignee['get_region_province']['region_name'];
                $consignee = $this->baseRepository->getArrayExcept($consignee, 'get_region_province');
            } else {
                $province_name = '';
            }

            $consignee['province_name'] = $province_name;

            if ($consignee['get_region_city']) {
                $city_name = $consignee['get_region_city']['region_name'];
                $consignee = $this->baseRepository->getArrayExcept($consignee, 'get_region_city');
            } else {
                $city_name = '';
            }

            $consignee['city_name'] = $city_name;

            if ($consignee['get_region_district']) {
                $district_name = $consignee['get_region_district']['region_name'];
                $consignee = $this->baseRepository->getArrayExcept($consignee, 'get_region_district');
            } else {
                $district_name = '';
            }

            $consignee['district_name'] = $district_name;

            if ($consignee['get_region_street']) {
                $street_name = $consignee['get_region_street']['region_name'];
                $consignee = $this->baseRepository->getArrayExcept($consignee, 'get_region_street');
            } else {
                $street_name = '';
            }

            $consignee['street_name'] = $street_name;

            $region = $province_name . " " . $city_name . " " . $district_name . " " . $street_name;
            $consignee['region'] = trim($region);
        }

        return $consignee;
    }

    /**
     * 获取收货地址的数量
     *
     * @param int $user_id
     * @param array $consignee
     * @return mixed
     */
    public function getUserAddressCount($user_id = 0, $consignee = [])
    {
        $res = UserAddress::where('user_id', $user_id);

        if ($consignee) {
            if (isset($consignee['consignee']) && $consignee['consignee']) {
                $res = $res->where('consignee', $consignee['consignee']);
            }

            if (isset($consignee['country']) && $consignee['country']) {
                $res = $res->where('country', $consignee['country']);
            }

            if (isset($consignee['province']) && $consignee['province']) {
                $res = $res->where('province', $consignee['province']);
            }

            if (isset($consignee['city']) && $consignee['city']) {
                $res = $res->where('city', $consignee['city']);
            }

            if (isset($consignee['district']) && $consignee['district']) {
                $res = $res->where('district', $consignee['district']);
            }

            if (isset($consignee['street']) && $consignee['street']) {
                $res = $res->where('street', $consignee['street']);
            }

            if ($consignee['address_id'] > 0) {
                $res = $res->where('address_id', '<>', $consignee['address_id']);
            }
        }

        return $res->count();
    }

    /**
     * 保存用户的收货人信息
     * 如果收货人信息中的 id 为 0 则新增一个收货人信息
     *
     * @access  public
     * @param array $consignee
     * @param boolean $default 是否将该收货人信息设置为默认收货人信息
     * @return  boolean
     */
    public function saveConsignee($consignee = [], $default = false)
    {
        $user_id = session('user_id', 0);

        $res = false;
        if ($consignee['address_id'] > 0) {
            /* 修改地址 */
            $res = UserAddress::where('address_id', $consignee['address_id'])->where('user_id', $consignee['user_id'])->update($consignee);
        } else {
            /* 添加地址 */
            $new_address = $this->baseRepository->getArrayfilterTable($consignee, 'user_address');
            try {
                $address_id = UserAddress::insertGetId($new_address);
            } catch (\Exception $e) {
                $error_no = (stripos($e->getMessage(), '1062 Duplicate entry') !== false) ? 1062 : $e->getCode();

                if ($error_no > 0 && $error_no != 1062) {
                    die($e->getMessage());
                }
            }
            $consignee['address_id'] = $address_id ?? 0;
        }

        if ($default) {
            /* 保存为用户的默认收货地址 */
            $res = Users::where('user_id', $user_id)->update(['address_id' => $consignee['address_id']]);
        }

        if ($res > 0) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 获取会员收货地址列表
     * @param int $user_id
     * @param int $num
     * @return array
     */
    public function getUserAddressList($user_id = 0, $num = 0)
    {
        $res = UserAddress::where('user_id', $user_id);

        $res = $res->with([
            'getUsers'
        ]);

        $res = $res->with([
            'getRegionProvince' => function ($query) {
                $query->select('region_id', 'region_name');
            },
            'getRegionCity' => function ($query) {
                $query->select('region_id', 'region_name');
            },
            'getRegionDistrict' => function ($query) {
                $query->select('region_id', 'region_name');
            },
            'getRegionStreet' => function ($query) {
                $query->select('region_id', 'region_name');
            }
        ]);

        if ($num > 0) {
            $res = $res->take($num);
        }

        $res = $res->orderBy('address_id');

        $res = $this->baseRepository->getToArrayGet($res);

        $arr = [];
        if ($res) {
            foreach ($res as $row) {
                $province_name = '';
                $city_name = '';
                $district_name = '';
                $street_name = '';
                if (isset($row['get_region_province'])) {
                    $province_name = $row['get_region_province']['region_name'];
                }
                $row['province_name'] = $province_name;

                if (isset($row['get_region_city'])) {
                    $city_name = $row['get_region_city']['region_name'];
                }
                $row['city_name'] = $city_name;

                if (isset($row['get_region_district'])) {
                    $district_name = $row['get_region_district']['region_name'];
                }
                $row['district_name'] = $district_name;

                if (isset($row['get_region_street'])) {
                    $street_name = $row['get_region_street']['region_name'];
                }
                $row['street_name'] = $street_name;

                $region = $province_name . " " . $city_name . " " . $district_name . " " . $street_name;
                $row['region'] = trim($region);
                // 默认用户收货地址id
                $row['is_checked'] = 0;
                if (isset($row['get_users'])) {
                    if ($row['address_id'] == $row['get_users']['address_id']) {
                        $row['is_checked'] = 1;
                    }
                }

                $arr[] = $row;
            }
        }

        return $arr;
    }

    /**
     * 删除会员收货地址
     * @param int $id
     * @param int $user_id
     * @return bool
     */
    public function dropConsignee($id = 0, $user_id = 0)
    {
        $userAddress = UserAddress::where('address_id', $id)->first();

        $uid = $userAddress->user_id ?? 0;

        if ($uid != $user_id) {
            return false;
        }

        return $userAddress->delete();
    }

    /**
     * 添加或更新指定用户收货地址
     * @param array $address
     * @param int $default 设置默认收货地址
     * @return bool
     */
    public function updateAddress($address = [], $default = 0)
    {
        if (empty($address)) {
            return false;
        }

        $address_id = intval($address['address_id']);

        if ($address_id > 0) {
            /* 更新指定记录 */
            UserAddress::where('address_id', $address_id)->where('user_id', $address['user_id'])->update($address);
        } else {
            if (isset($address['address_id'])) {
                unset($address['address_id']);
            }
            /* 插入一条新记录 */
            $address_id = UserAddress::insertGetId($address);
        }

        if ($address_id > 0) {
            $res_count = UserAddress::where('user_id', $address['user_id'])->count();

            if ($res_count == 1) {
                Users::where('user_id', $address['user_id'])->update(['address_id' => $address_id]);
                session(['flow_consignee' => $address]);
            }
        }

        if ($default > 0 && !empty($address['user_id'])) {
            Users::where('user_id', $address['user_id'])->update(['address_id' => $address_id]);
        }

        return true;
    }

    /**
     * 添加或更新指定用户收货地址
     *
     * @param int $address_id
     * @param int $user_id
     * @return array
     */
    public function getUpdateFlowConsignee($address_id = 0, $user_id = 0)
    {
        $consignee = [];
        if ($address_id) {
            Users::where('user_id', $user_id)->update(['address_id' => $address_id]);

            $consignee = UserAddress::where('address_id', $address_id)->where('user_id', $user_id)->first();

            $consignee = $consignee ? $consignee->toArray() : [];
        }

        return $consignee;
    }


    /**
     * 查询用户地址信息
     *
     * @param int $order_id
     * @param string $address
     * @param int $type
     * @return string
     */
    public function getUserRegionAddress($order_id = 0, $address = '', $type = 0)
    {
        /* 取得区域名 */
        if ($type == 1) {
            $res = OrderReturn::where('ret_id', $order_id);
        } elseif ($type == 2) {
            $res = WholesaleOrderInfo::where('order_id', $order_id);
        } elseif ($type == 3) {
            $res = GroupbuyOrder::where('order_id', $order_id);
        } else {
            $res = OrderInfo::where('order_id', $order_id);
        }

        $res = $res->with([
            'getRegionProvince' => function ($query) {
                $query->select('region_id', 'region_name');
            },
            'getRegionCity' => function ($query) {
                $query->select('region_id', 'region_name');
            },
            'getRegionDistrict' => function ($query) {
                $query->select('region_id', 'region_name');
            },
            'getRegionStreet' => function ($query) {
                $query->select('region_id', 'region_name');
            }
        ]);

        $res = $this->baseRepository->getToArrayFirst($res);

        $region = '';
        if ($res) {
            if ($res['get_region_province']) {
                $province_name = $res['get_region_province']['region_name'];
            } else {
                $province_name = '';
            }

            $consignee['province_name'] = $province_name;

            if ($res['get_region_city']) {
                $city_name = $res['get_region_city']['region_name'];
            } else {
                $city_name = '';
            }

            $res['city_name'] = $city_name;

            if ($res['get_region_district']) {
                $district_name = $res['get_region_district']['region_name'];
            } else {
                $district_name = '';
            }

            $res['district_name'] = $district_name;

            if ($res['get_region_street']) {
                $street_name = $res['get_region_street']['region_name'];
            } else {
                $street_name = '';
            }

            $res['street_name'] = $street_name;

            $region = $province_name . " " . $city_name . " " . $district_name . " " . $street_name;
            $region = trim($region);
            if ($address) {
                $region = $region . " " . $address;
            }
        }

        return $region;
    }

    /**
     * 同步微信收货地址
     * @param array $wximport
     * @return array
     */
    public function wximportInfo($wximport = [])
    {
        $info = [];

        $info['consignee'] = $wximport['userName'] ?? '';
        $info['mobile'] = $wximport['telNumber'] ?? '';
        $info['address'] = $wximport['detailInfo'] ?? '';

        $province = $wximport['provinceName'] ?? '';
        $city = $wximport['cityName'] ?? '';
        $district = $wximport['countyName'] ?? '';

        //取得省的ID
        $region_array = [
            'region_type' => 1,
            'region_name' => $province
        ];

        $region = $this->getRegion($region_array);
        $info['province'] = $region['region_id'] ?? 0;//省id
        $province_name = $region['region_name'] ?? '';

        //取得市的ID
        $region_array = [
            'region_type' => 2,
            'region_name' => $city
        ];
        $region = $this->getRegion($region_array);
        $info['city'] = $region['region_id'] ?? 0;//市id
        $city_name = $region['region_name'] ?? '';

        //取得地区ID
        $region_array = [
            'region_type' => 3,
            'region_name' => $district
        ];
        $region = $this->getRegion($region_array);
        $info['district'] = $region['region_id'] ?? 0;//区id
        $district_name = $region['region_name'] ?? '';

        $region = $province_name . " " . $city_name . " " . $district_name;
        $info['region'] = trim($region);


        return $info;

    }

    /**
     * 获取地区
     * @param array $region_array
     * @return array
     */
    public function getRegion($region_array = [])
    {
        $region = Region::where($region_array)->first();

        $region = $region ? $region->toArray() : [];

        return $region;

    }

    /**
     * 获取用户默认收货地址
     *
     * @param int $user_id
     * @return array
     */
    public function getDefaultByUserId($user_id = 0)
    {
        $address_id = Users::where('user_id', $user_id)->value('address_id');
        $address = [];
        if ($address_id) {
            $address = UserAddress::where('address_id', $address_id)->first();
            $address = $address ? $address->toArray() : [];
        }

        return $address;
    }
}