<?php

namespace App\Repositories\Common;

use App\Kernel\Repositories\Common\DscRepository as Base;
use App\Models\MerchantsShopInformation;
use App\Models\RsRegion;

/**
 * Class DscRepository
 * @method getPriceFormat($price = 0, $change_price = true) 格式化商品价格
 * @method getImagePath($image = '') 重新获得商品图片与商品相册的地址
 * @method getContentImgReplace($content) 正则批量替换详情内图片为 绝对路径
 * @method getDdownloadTemplate($path = '') 处理指定目录文件数据调取
 * @method objectToArray($obj) 对象转数组
 * @method getReturnMobile($url = '') 跳转H5方法
 * @method pageArray($page_size = 1, $page = 1, $array = [], $order = 0, $filter_arr = []) 数组分页函数 核心函数 array_slice
 * @method getPatch() 升级补丁SQL
 * @method readModules($directory = '.') 获得所有模块的名称以及链接地址
 * @method objectArray($array = null) 对象转数组
 * @method getInciseDirectory($list = []) 切割目录文件
 * @method mysqlLikeQuote($str) 对 MYSQL LIKE 的内容进行转义
 * @method stringToStar($string = '', $num = 3, $start_len = '') 将字符串以 * 号格式显示 配合msubstr_ect函数使用
 * @method msubstrEct($str = '', $start = 0, $length = 1, $charset = "utf-8", $suffix = '***', $position = 1) 字符串截取，支持中文和其他编码
 * @method dscIp() 获取用户IP ：可能会出现误差
 * @method contentStyleReplace($content) 正则过滤内容样式 style = '' width = '' height = ''
 * @method helpersLang($files = [], $module = '', $langPath = 0) 组合语言包信息
 * @method readStaticCache($cache_path = '', $cache_name = '', $storage_path = 'common_cache/', $prefix = "php") 读结果缓存文件
 * @method writeStaticCache($cache_path = '', $cache_name = '', $caches = '', $storage_path = 'common_cache/', $prefix = "php") 写结果缓存文件
 * @method getHttpBasename($url = '', $path = '', $goods_lib = '') 下载远程图片
 * @method remoteLinkExists($url) 判断远程链接|判断本地链接 -- 是否存在
 * @method pluginsLang($plugin, $dir) 调取插件语言包[插件名称(Alipay/), __DIR__]
 * @method subStr($str, $length = 0, $append = true) 截取UTF-8编码下字符串的函数
 * @method trimRight($str) 去除字符串右侧可能出现的乱码
 * @method strLen($str = '') 计算字符串的长度（汉字按照两个字符计算）
 * @method delStrComma($str = '', $delstr = ',') 去除字符串中首尾逗号[去除字符串中出现两个连续逗号]
 * @method getBucketInfo()【云存储】获取存储信息
 * @method getOssAddFile($file = [])【云存储】上传文件
 * @method getOssDelFile($file = [])【云存储】删除文件
 * @method getDelBatch($checkboxs = '', $val_id = '', $select = '', $id = '', $query, $del = 0, $fileDir = '')【云存储】单个或批量删除图片
 * @method getDelVisualTemplates($ip = [], $suffix = '', $act = 'del_hometemplates', $seller_id = 0)【云存储】删除可视化模板OSS标识文件
 * @method getOssListFile($file = [])【云存储】下载文件
 * @method dscEmpower($AppKey, $activate_time) 生成授权证书
 * @method checkEmpower() 校验授权
 * @method collateOrderGoodsBonus($bonus_list = [], $orderBonus = 0, $goods_bonus = 0) 核对均摊红包商品金额是否大于订单红包金额
 * @method collateOrderGoodsCoupons($coupons_list = [], $orderCoupons = 0, $goods_coupons = 0) 核对均摊优惠券商品金额是否大于订单红包金额
 * @method dscConfig($str = '') $str默认值空，多个示例:xx, xx, xx字符串组成
 * @method dscUrl($str = '') 获取网站地址[域名]
 * @method turnPluckFlattenOne($goods_list = [], $key = 'goods_list') 提取数组数据
 * @method chatQq($basic_info) 处理系统设置[QQ客服/旺旺客服]
 * @method shippingFee($shipping_code = '', $shipping_config = '', $goods_weight = 0, $goods_amount = 0, $goods_number = 0) 计算运费
 * @method valueOfIntegral($integral = 0) 计算积分的价值（能抵多少钱）
 * @method integralOfValue($value = 0) 计算指定的金额需要多少积分
 * @method changeFloat($float = 0) 转浮点值，保存两位
 * @method dscHttp($server = '') 获取http|https
 * @method isJsonp($back_act = '', $exp = '|', $strpos = 'is_jsonp') 获取店铺二级域名跨域关键值
 * @method hostDomain($url = '') 获取主域名
 * @method getUrlHtml($list = ['index', 'user']) 返回html链接： http://www.xxx.com/,http://www.xxx.com/user.html
 * @package App\Repositories\Common
 */
class DscRepository extends Base
{
    /**
     * 重写 URL 地址
     *
     * @param string $app 执行程序
     * @param array $params 参数数组
     * @param string $append 附加字串
     * @param int $page 页数
     * @param string $keywords 搜索关键词字符串
     * @return bool|\Illuminate\Contracts\Routing\UrlGenerator|string
     */
    public function buildUri($app = '', $params = [], $append = '', $page = 0, $keywords = '')
    {
        static $rewrite = null;

        if ($rewrite === null) {
            $rewrite = intval($this->config['rewrite']);
        }

        /* 初始值 */
        $cid = 0;
        $chkw = '';
        $secid = 0;
        $tmr = '';

        $args = [
            'cid' => 0,
            'gid' => 0,
            'bid' => 0,
            'acid' => 0,
            'aid' => 0,
            'mid' => 0,
            'urid' => 0,
            'ubrand' => 0,
            'chkw' => '',
            'is_ship' => '',
            'hid' => 0,
            'sid' => 0,
            'gbid' => 0,
            'auid' => 0,
            'sort' => '',
            'order' => '',
            'status' => -1,
            'secid' => 0,
            'tmr' => 0
        ];

        extract(array_merge($args, $params));

        $uri = '';
        switch ($app) {
            case 'history_list':
                if ($rewrite) {
                    $uri = 'history_list-' . $cid;

                    if (!empty($page)) {
                        $uri .= '-' . $page;
                    }
                } else {
                    $uri = 'history_list.php?cat_id=' . $cid;

                    if (!empty($page)) {
                        $uri .= '&amp;page=' . $page;
                    }
                }

                break;

            case 'category':
                if (empty($cid)) {
                    return false;
                } else {
                    if ($rewrite) {
                        $uri = 'category-' . $cid;
                        if (isset($bid) && !empty($bid)) {
                            $uri .= '-b' . $bid;
                        }

                        //ecmoban模板堂 --zhuo start
                        if (isset($ubrand) && !empty($ubrand)) {
                            $uri .= '-ubrand' . $ubrand;
                        }
                        //ecmoban模板堂 --zhuo end

                        if (isset($price_min)) {
                            $uri .= '-min' . $price_min;
                        }
                        if (isset($price_max)) {
                            $uri .= '-max' . $price_max;
                        }
                        if (isset($filter_attr) && $filter_attr) {
                            $uri .= '-attr' . $filter_attr;
                        }
                        if (isset($ship) && !empty($ship)) {
                            $uri .= '-ship' . $ship;
                        }
                        if (isset($self) && !empty($self)) {
                            $uri .= '-self' . $self;
                        }
                        if (isset($have) && !empty($have)) {
                            $uri .= '-have' . $have;
                        }
                        if (!empty($page)) {
                            $uri .= '-' . $page;
                        }
                        if (!empty($sort)) {
                            $uri .= '-' . $sort;
                        }
                        if (!empty($order)) {
                            $uri .= '-' . $order;
                        }
                    } else {
                        $uri = 'category.php?id=' . $cid;
                        if (!empty($bid)) {
                            $uri .= '&amp;brand=' . $bid;
                        }

                        if (!empty($ubrand)) {
                            $uri .= '&amp;ubrand=' . $ubrand;
                        }

                        if (isset($price_min) && !empty($price_min)) {
                            $uri .= '&amp;price_min=' . $price_min;
                        }
                        if (isset($price_max) && !empty($price_max)) {
                            $uri .= '&amp;price_max=' . $price_max;
                        }

                        if (isset($filter_attr) && !empty($filter_attr)) {
                            $uri .= '&amp;filter_attr=' . $filter_attr;
                        }

                        if (isset($ship) && !empty($ship)) {
                            $uri .= '&amp;ship=' . $ship;
                        }

                        if (isset($self) && !empty($self)) {
                            $uri .= '&amp;self=' . $self;
                        }

                        if (isset($have) && !empty($have)) {
                            $uri .= '&amp;have=' . $have;
                        }

                        if (!empty($page)) {
                            $uri .= '&amp;page=' . $page;
                        }
                        if (!empty($sort)) {
                            $uri .= '&amp;sort=' . $sort;
                        }
                        if (!empty($order)) {
                            $uri .= '&amp;order=' . $order;
                        }
                    }
                }

                break;

            case 'wholesale':
                if (empty($cid) && empty($act)) {
                    return false;
                } else {
                    if ($rewrite) {
                        $uri = 'wholesale';
                        if (!empty($cid)) {
                            $uri .= '-' . $cid;
                        }

                        if (!empty($cid)) {
                            $uri .= '-c' . $cid;
                        }

                        if (isset($status) && $status != -1) {
                            $uri .= '-status' . $status;
                        }

                        if (!empty($act)) {
                            $uri .= '-' . $act;
                        }
                    } else {
                        $uri = 'wholesale.php?';
                        if (!empty($act)) {
                            $uri .= 'act=' . $act;
                        }
                        if (!empty($cid)) {
                            $uri .= '&amp;id=' . $cid;
                        }

                        if (isset($status) && $status != -1) {
                            $uri .= '&amp;status=' . $status;
                        }
                    }
                }

                break;

            case 'wholesale_cat':
                if (empty($cid) && empty($act)) {
                    return false;
                } else {
                    if ($rewrite) {
                        $uri = 'wholesale_cat';
                        if (!empty($cid)) {
                            $uri .= '-' . $cid;
                        }

                        if (isset($status) && $status != -1) {
                            $uri .= '-status' . $status;
                        }

                        if (!empty($act)) {
                            $uri .= '-' . $act;
                        }
                    } else {
                        $uri = 'wholesale_cat.php?';

                        if (!empty($cid)) {
                            $uri .= 'id=' . $cid;
                        }
                        if (isset($status) && $status != -1) {
                            $uri .= '&amp;status=' . $status;
                        }

                        if (!empty($act)) {
                            $uri .= '&amp;act=' . $act;
                        }

                        if (!empty($page)) {
                            $uri .= '&amp;page=' . $page;
                        }
                    }
                }

                break;

            case 'wholesale_suppliers':
                if (empty($sid) && empty($act)) {
                    return false;
                } else {
                    if ($rewrite) {
                        $uri = 'wholesale_suppliers';
                        if (!empty($sid)) {
                            $uri .= '-' . $sid;
                        }

                        if (isset($status) && $status != -1) {
                            $uri .= '-status' . $status;
                        }

                        if (!empty($act)) {
                            $uri .= '-' . $act;
                        }
                    } else {
                        $uri = 'wholesale_suppliers.php?';

                        if (!empty($sid)) {
                            $uri .= 'suppliers_id=' . $sid;
                        }

                        if (!empty($act)) {
                            $uri .= '&amp;act=' . $act;
                        }

                        if (!empty($page)) {
                            $uri .= '&amp;page=' . $page;
                        }
                    }
                }

                break;

            case 'wholesale_goods':
                if (empty($aid)) {
                    return false;
                } else {
                    $uri = $rewrite ? 'wholesale_goods-' . $aid : 'wholesale_goods.php?id=' . $aid;
                }

                break;

            case 'wholesale_purchase':
                if (empty($gid) && empty($act)) {
                    return false;
                } else {
                    if ($rewrite) {
                        $uri = 'wholesale_purchase';
                        if (!empty($gid)) {
                            $uri .= '-' . $gid;
                        }

                        if (!empty($act)) {
                            $uri .= '-' . $act;
                        }
                    } else {
                        $uri = 'wholesale_purchase.php?';

                        if (!empty($gid)) {
                            $uri .= 'id=' . $gid;
                        }

                        if (!empty($act)) {
                            $uri .= '&amp;act=' . $act;
                        }
                    }
                }

                break;

            case 'goods':
                if (empty($gid)) {
                    return false;
                } else {
                    $uri = $rewrite ? 'goods-' . $gid : 'goods.php?id=' . $gid;
                }

                break;
            case 'presale':
                if (empty($presaleid) && empty($act)) {
                    return false;
                } else {
                    if ($rewrite) {
                        $uri = 'presale';
                        if (!empty($presaleid)) {
                            $uri .= '-' . $presaleid;
                        }

                        if (!empty($cid)) {
                            $uri .= '-c' . $cid;
                        }

                        if (isset($status) && $status != -1) {
                            $uri .= '-status' . $status;
                        }

                        if (!empty($act)) {
                            $uri .= '-' . $act;
                        }
                    } else {
                        $uri = 'presale.php?';
                        if (!empty($presaleid)) {
                            $uri .= 'id=' . $presaleid;
                        }

                        if (!empty($cid)) {
                            $uri .= 'cat_id=' . $cid;
                        }

                        if (isset($status) && $status != -1) {
                            $uri .= '&amp;status=' . $status;
                        }

                        if (!empty($act)) {
                            $uri .= '&amp;act=' . $act;
                        }
                    }
                }

                break;
            case 'categoryall':
                if (empty($urid)) {
                    return false;
                } else {
                    if ($rewrite) {
                        $uri = 'categoryall';
                        if (!empty($urid)) {
                            $uri .= '-' . $urid;
                        }
                    } else {
                        $uri = 'categoryall.php';
                        if (!empty($urid)) {
                            $uri .= '?id=' . $urid;
                        }
                    }
                }

                break;
            case 'brand':
                if (empty($bid)) {
                    return false;
                } else {
                    if ($rewrite) {
                        $uri = 'brand-' . $bid;

                        if (!empty($mbid)) {
                            $uri .= '-mbid' . $mbid;
                        }

                        if (!empty($cid)) {
                            $uri .= '-c' . $cid;
                        }
                        //by wang start
                        if (isset($price_min) && !empty($price_min)) {
                            $uri .= '-min' . $price_min;
                        }
                        if (isset($price_max) && !empty($price_max)) {
                            $uri .= '-max' . $price_max;
                        }
                        if (isset($ship) && !empty($ship)) {
                            $uri .= '-ship' . $ship;
                        }
                        if (isset($self) && !empty($self)) {
                            $uri .= '-self' . $self;
                        }
                        //by wang end

                        if (!empty($page)) {
                            $uri .= '-' . $page;
                        }
                        if (!empty($sort)) {
                            $uri .= '-' . $sort;
                        }
                        if (!empty($order)) {
                            $uri .= '-' . $order;
                        }
                    } else {
                        $uri = 'brand.php?id=' . $bid;

                        if (!empty($mbid)) {
                            $uri .= '&amp;mbid=' . $mbid;
                        }

                        if (!empty($cid)) {
                            $uri .= '&amp;cat_id=' . $cid;
                        }
                        //by wang start
                        if (isset($price_min)) {
                            $uri .= '&amp;price_min=' . $price_min;
                        }
                        if (isset($price_max)) {
                            $uri .= '&amp;price_max=' . $price_max;
                        }
                        if (isset($ship) && !empty($ship)) {
                            $uri .= '&amp;ship=' . $ship;
                        }
                        if (isset($self) && !empty($self)) {
                            $uri .= '&amp;self=' . $self;
                        }
                        if (!empty($page)) {
                            $uri .= '&amp;page=' . $page;
                        }
                        //by wang end
                        if (!empty($sort)) {
                            $uri .= '&amp;sort=' . $sort;
                        }
                        if (!empty($order)) {
                            $uri .= '&amp;order=' . $order;
                        }
                    }
                }

                break;
            case 'brandn':
                if (empty($bid)) {
                    return false;
                } else {
                    if ($rewrite) {
                        $uri = 'brandn-' . $bid;
                        if (isset($cid) && !empty($cid)) {
                            $uri .= '-c' . $cid;
                        }
                        if (!empty($page)) {
                            $uri .= '-' . $page;
                        }

                        if (!empty($sort)) {
                            $uri .= '-' . $sort;
                        }
                        if (!empty($order)) {
                            $uri .= '-' . $order;
                        }
                        if (!empty($act)) {
                            $uri .= '-' . $act;
                        }
                    } else {
                        $uri = 'brandn.php?id=' . $bid;
                        if (!empty($cid)) {
                            $uri .= '&amp;cat_id=' . $cid;
                        }
                        if (!empty($page)) {
                            $uri .= '&amp;page=' . $page;
                        }
                        if (isset($price_min)) {
                            $uri .= '&amp;price_min=' . $price_min;
                        }
                        if (isset($price_max)) {
                            $uri .= '&amp;price_max=' . $price_max;
                        }
                        if (isset($is_ship) && !empty($is_ship)) {
                            $uri .= '&amp;is_ship=' . $is_ship;
                        }
                        if (!empty($sort)) {
                            $uri .= '&amp;sort=' . $sort;
                        }
                        if (!empty($order)) {
                            $uri .= '&amp;order=' . $order;
                        }
                        if (!empty($act)) {
                            $uri .= '&amp;act=' . $act;
                        }
                    }
                }

                break;
            case 'article_cat':
                if (empty($acid)) {
                    return false;
                } else {
                    if ($rewrite) {
                        $uri = 'article_cat-' . $acid;
                        if (!empty($page)) {
                            $uri .= '-' . $page;
                        }
                        if (!empty($sort)) {
                            $uri .= '-' . $sort;
                        }
                        if (!empty($order)) {
                            $uri .= '-' . $order;
                        }
                        if (!empty($keywords)) {
                            $uri .= '-' . $keywords;
                        }
                    } else {
                        $uri = 'article_cat.php?id=' . $acid;
                        if (!empty($page)) {
                            $uri .= '&amp;page=' . $page;
                        }
                        if (!empty($sort)) {
                            $uri .= '&amp;sort=' . $sort;
                        }
                        if (!empty($order)) {
                            $uri .= '&amp;order=' . $order;
                        }
                        if (!empty($keywords)) {
                            $uri .= '&amp;keywords=' . $keywords;
                        }
                    }
                }

                break;
            case 'article':
                if (empty($aid)) {
                    return false;
                } else {
                    $uri = $rewrite ? 'article-' . $aid : 'article.php?id=' . $aid;
                }

                break;
            case 'merchants':
                if (empty($mid)) {
                    return false;
                } else {
                    $uri = $rewrite ? 'merchants-' . $mid : 'merchants.php?id=' . $mid;
                }

                break;
            case 'merchants_index':
                if (empty($urid) && empty($merchant_id)) {
                    return false;
                } else {
                    if ($urid) {
                        if ($rewrite) {
                            $uri = '';
                            $uri .= 'merchants_index-' . $urid;
                        } else {
                            $uri = 'merchants_index.php?merchant_id=' . $urid;
                        }
                    }

                    if ($merchant_id) {
                        if ($rewrite) {
                            $uri = '';
                            $uri .= 'merchants_index-' . $merchant_id;
                        } else {
                            $uri = 'merchants_index.php?merchant_id=' . $merchant_id;
                        }
                    }
                }

                break;
            case 'merchants_store':
                if (empty($urid)) {
                    return false;
                } else {

                    if ($rewrite) {
                        $storeUrl = 'merchants_store-' . $urid;
                    } else {
                        $storeUrl = 'merchants_store.php?merchant_id=' . $urid;
                    }

                    $uri .= $this->merchantsStoreUrl($storeUrl, $cid ?? 0, $bid ?? 0, $keyword ?? '', $price_min ?? 0, $price_max ?? 0, $filter_attr ?? '', $page ?? 0, $sort ?? '', $order ?? '');
                }
                break;

            case 'merchants_store_shop':
                if (empty($urid)) {
                    return false;
                } else {
                    if ($rewrite) {
                        $uri .= 'merchants_store_shop-' . $urid;

                        if (!empty($page)) {
                            $uri .= '-' . $page;
                        }
                        if (!empty($sort)) {
                            $uri .= '-' . $sort;
                        }
                        if (!empty($order)) {
                            $uri .= '-' . $order;
                        }
                    } else {
                        $uri = 'merchants_store_shop.php?id=' . $urid;

                        if (!empty($page)) {
                            $uri .= '&amp;page=' . $page;
                        }
                        if (!empty($sort)) {
                            $uri .= '&amp;sort=' . $sort;
                        }
                        if (!empty($order)) {
                            $uri .= '&amp;order=' . $order;
                        }
                    }
                }
                break;
            case 'group_buy':
                if (empty($gbid)) {
                    return false;
                } else {
                    $uri = $rewrite ? 'group_buy-' . $gbid : 'group_buy.php?act=view&amp;id=' . $gbid;
                }

                break;
            case 'auction':
                if (empty($auid)) {
                    return false;
                } else {
                    $uri = $rewrite ? 'auction-' . $auid : 'auction.php?act=view&amp;id=' . $auid;
                }

                break;
            case 'snatch':
                if (empty($sid)) {
                    return false;
                } else {
                    $uri = $rewrite ? 'snatch-' . $sid : 'snatch.php?id=' . $sid;
                }

                break;
            case 'history_list':
                if (empty($hid)) {
                    return false;
                } else {
                    $uri = $rewrite ? 'history_list-' . $hid : 'history_list.php?act=user&amp;id=' . $hid;
                }

                break;
            case 'search':
                $uri = 'search.php?keywords=' . $chkw;

                if (!empty($bid)) {
                    $uri .= '&amp;brand=' . $bid;
                }
                if (isset($price_min)) {
                    $uri .= '&amp;price_min=' . $price_min;
                }
                if (isset($price_max)) {
                    $uri .= '&amp;price_max=' . $price_max;
                }
                if (!empty($filter_attr)) {
                    $uri .= '&amp;filter_attr=' . $filter_attr;
                }
                if (!empty($cou_id)) {
                    $uri .= '&amp;cou_id=' . $cou_id;
                }
                break;
            case 'user':
                if (empty($act)) {
                    return false;
                } else {
                    if ($rewrite) {
                        $uri = 'user';
                        if (!empty($act)) {
                            $uri .= '-' . $act;
                        }
                    } else {
                        $uri = 'user.php?';
                        if (!empty($act)) {
                            $uri .= 'act=' . $act;
                        }
                    }
                }

                break;
            case 'exchange':
                if (empty($cid)) {
                    if (!empty($page)) {
                        $uri = 'exchange-' . $cid;
                        if ($rewrite) {
                            $uri .= '-' . $page;
                        } else {
                            $uri = 'exchange.php?';
                            $uri .= 'page=' . $page;
                        }
                    } else {
                        return false;
                    }
                } else {
                    if ($rewrite) {
                        $uri = 'exchange-' . $cid;
                        if (isset($price_min)) {
                            $uri .= '-min' . $price_min;
                        }
                        if (isset($price_max)) {
                            $uri .= '-max' . $price_max;
                        }
                        if (!empty($page)) {
                            $uri .= '-' . $page;
                        }
                        if (!empty($sort)) {
                            $uri .= '-' . $sort;
                        }
                        if (!empty($order)) {
                            $uri .= '-' . $order;
                        }
                    } else {
                        $uri = 'exchange.php?cat_id=' . $cid;
                        if (isset($price_min)) {
                            $uri .= '&amp;integral_min=' . $price_min;
                        }
                        if (isset($price_max)) {
                            $uri .= '&amp;integral_max=' . $price_max;
                        }

                        if (!empty($page)) {
                            $uri .= '&amp;page=' . $page;
                        }
                        if (!empty($sort)) {
                            $uri .= '&amp;sort=' . $sort;
                        }
                        if (!empty($order)) {
                            $uri .= '&amp;order=' . $order;
                        }
                    }
                }

                break;
            case 'exchange_goods':
                if (empty($gid)) {
                    return false;
                } else {
                    $uri = $rewrite ? 'exchange-id' . $gid : 'exchange.php?id=' . $gid . '&amp;act=view';
                }
                break;
            case 'gift_gard':
                if (empty($cid)) {
                    return false;
                } else {
                    if ($rewrite) {
                        $uri = 'gift_gard-' . $cid;
                        if (!empty($page)) {
                            $uri .= '-' . $page;
                        }
                        if (!empty($sort)) {
                            $uri .= '-' . $sort;
                        }
                        if (!empty($order)) {
                            $uri .= '-' . $order;
                        }
                    } else {
                        $uri = 'gift_gard.php?cat_id=' . $cid;
                        if (!empty($page)) {
                            $uri .= '&amp;page=' . $page;
                        }
                        if (!empty($sort)) {
                            $uri .= '&amp;sort=' . $sort;
                        }
                        if (!empty($order)) {
                            $uri .= '&amp;order=' . $order;
                        }
                    }
                }
                break;
            case 'seckill':
                if (empty($act)) {
                    if (!empty($cid)) {
                        $uri = $rewrite ? 'seckill-' . $cid : 'seckill.php?cat_id=' . $cid;
                    } else {
                        return false;
                    }
                } else {
                    if ($rewrite) {
                        $uri = 'seckill-' . $secid;

                        if (!empty($act)) {
                            $uri .= '-' . $act;
                        }
                    } else {
                        $uri = 'seckill.php?id=' . $secid;

                        if ($act == 'view') {
                            $uri .= "&act=view";
                        }
                        if ($tmr) {
                            $uri .= "&tmr=1";
                        }
                    }
                }

                break;
            default:
                return false;
                break;
        }

        if ($rewrite) {
            if ($rewrite == 2 && !empty($append)) {
                $uri .= '-' . urlencode(preg_replace('/[\.|\/|\?|&|\+|\\\|\'|"|,]+/', '', $append));
            }

            if (!in_array($app, ['search'])) {
                $uri .= '.html';
            }
        }
        if (($rewrite == 2) && (strpos(strtolower(EC_CHARSET), 'utf') !== 0)) {
            $uri = urlencode($uri);
        }

        return $this->dscUrl($uri, config('app.url'));
    }

    /**
     * 判断所属后台
     *
     * @param int $type
     * @return int
     */
    public function getAdminPathType($type = -1)
    {
        $self = explode("/", substr(request()->getRequestUri(), 1));
        $count = count($self);

        if ($count > 1) {
            $real_path = $self['0'];
            if ($real_path == ADMIN_PATH) {
                /* 平台后台 */
                $type = 0;
            } elseif ($real_path == SELLER_PATH) {
                /* 商家后台 */
                $type = 1;
            } elseif ($real_path == STORES_PATH) {
                /* 门店后台 */
                $type = 2;
            } elseif ($real_path == SUPPLLY_PATH) {
                /* 供货商后台 */
                $type = 3;
            }
        }

        return $type;
    }

    /**
     * 处理编辑器内容图片
     *
     * @param string $text_desc 编辑文本
     * @param string $str_file 模板调用变量
     * @param int $is_mobile_div 0|不过滤div，1|过滤div
     * @return array
     * @throws \Exception
     */
    public function descImagesPreg($text_desc = '', $str_file = 'goods_desc', $is_mobile_div = 0)
    {
        if ($str_file === 'desc_mobile' && $is_mobile_div == 1) {
            $text_desc = preg_replace('/<div[^>]*(tools)[^>]*>(.*?)<\/div>(.*?)<\/div>/is', '', $text_desc);
        }

        if ($this->config['open_oss'] == 1) {
            $bucket_info = $this->getBucketInfo();
            $endpoint = $bucket_info['endpoint'];
        } else {
            $endpoint = $this->dscUrl();
        }

        $endpoint = rtrim($endpoint, '/') . '/';
        $image_dir = $this->imageDir();
        $data_dir = $this->dataDir();

        $pathImage = [
            $this->dscUrl('storage/' . $image_dir . '/'),
            $this->dscUrl($image_dir . '/')
        ];

        $pathData = [
            $this->dscUrl('storage/' . $data_dir . '/'),
            $this->dscUrl($data_dir . '/')
        ];

        $uploads = $this->dscUrl('storage/uploads/');

        if ($text_desc) {
            $text_desc = stripcslashes($text_desc);
            $preg = '/<img.*?src=[\"|\']?(.*?)[\"|\'].*?>/i';
            preg_match_all($preg, $text_desc, $desc_img);
        } else {
            $desc_img = '';
        }

        $arr = [];
        if ($desc_img) {
            $img_list = isset($desc_img[1]) && $desc_img[1] ? array_unique($desc_img[1]) : [];//剔除重复值，防止重复添加域名

            if ($img_list && $endpoint) {
                foreach ($img_list as $key => $row) {
                    $row = trim($row);
                    if ($this->config['open_oss'] == 1) {
                        if (strpos($row, $this->dscUrl('storage/' . $image_dir)) !== false || strpos($row, $this->dscUrl($image_dir)) !== false) {
                            $row = str_replace($pathImage, '', $row);
                            $arr[] = 'storage/' . $image_dir . '/' . $row;

                            $text_desc = str_replace($pathImage, $endpoint . $image_dir . '/', $text_desc);
                        } elseif (strpos($row, $this->dscUrl('storage/' . $data_dir)) !== false || strpos($row, $this->dscUrl($data_dir)) !== false) {
                            $row = str_replace($pathData, '', $row);
                            $arr[] = 'storage/' . $data_dir . '/' . $row;

                            $text_desc = str_replace($pathData, $endpoint . $data_dir . '/', $text_desc);
                        } elseif (strpos($row, $uploads) !== false) {
                            $arr[] = 'storage/uploads/' . $row;
                        }
                    } else {
                        if (strpos($row, 'http://') !== false || strpos($row, 'https://') !== false) {
                            if (strpos($row, 'storage/' . $image_dir) !== false || strpos($row, $image_dir) !== false) {
                                $row = str_replace($pathImage, '', $row);
                                $arr[] = 'storage/' . $image_dir . '/' . $row;

                                $text_desc = str_replace($pathImage, $this->dscUrl('storage/' . $image_dir . '/'), $text_desc);
                            } elseif (strpos($row, 'storage/' . $data_dir) !== false || strpos($row, $data_dir) !== false) {
                                $row = str_replace($pathData, '', $row);
                                $arr[] = 'storage/' . $data_dir . '/' . $row;

                                $text_desc = str_replace($pathData, $this->dscUrl('storage/' . $data_dir . '/'), $text_desc);
                            } elseif (strpos($row, $uploads) !== false) {
                                $arr[] = 'storage/uploads/' . $row;
                            }
                        } else {
                            if (strpos($row, 'storage') !== false) {
                                $arr[] = $row;
                                $text_desc = str_replace($row, $this->dscUrl($row), $text_desc);
                            } else {
                                $arr[] = 'storage/' . $row;
                                $text_desc = str_replace($row, $this->dscUrl('storage/' . $row), $text_desc);
                            }
                        }
                    }
                }
            }
        }

        $res = [
            'images_list' => $arr,
            $str_file => $text_desc
        ];
        return $res;
    }

    /**
     * 获得图片的目录路径
     *
     * @param int $sid
     *
     * @return string 路径
     */
    public function imageDir($sid = 0)
    {
        if (empty($sid)) {
            $s = 'images';
        } else {
            $s = 'user_files/';
            $s .= ceil($sid / 3000) . '/';
            $s .= ($sid % 3000) . '/';
            $s .= 'images';
        }
        return $s;
    }

    /**
     * 获得数据目录的路径
     *
     * @param int $sid
     *
     * @return string 路径
     */
    public function dataDir($sid = 0)
    {
        if (empty($sid)) {
            $s = 'data';
        } else {
            $s = 'user_files/';
            $s .= ceil($sid / 3000) . '/';
            $s .= $sid % 3000;
        }
        return $s;
    }

    /**
     * 获取分类数组进行转换
     *
     * @param array $list
     * @param string $str
     * @return array
     */
    public function getCatVal($list = [], $str = 'cat_id')
    {
        $arr = [];
        if ($list) {
            foreach ($list as $key => $val) {
                $arr[$key][$str] = $val[$str];
                $arr[$key]['cat_list'] = $this->getCatTree($val['cat_list'], $str);
            }
        }

        return $arr;
    }

    /**
     * 获取分类数组进行转换
     *
     * @param array $list
     * @param string $str
     * @return array
     */
    public function getCatTree($list = [], $str = 'cat_id')
    {
        $arr = [];
        if ($list) {
            foreach ($list as $key => $val) {
                $arr[$key][$str] = $val[$str];
                $arr[$key]['cat_list'] = $this->getCatTree($val['cat_list']);
            }
        }

        return $arr;
    }

    /**
     * 计算积分的价值（能抵多少钱）
     *
     * @param int $integral 积分
     * @return float|int 积分价值
     */
    public function getValueOfIntegral($integral = 0)
    {
        $scale = floatval($this->config['integral_scale']);

        return $scale > 0 ? round(($integral / 100) * $scale, 2) : 0;
    }

    /**
     * 计算指定的金额需要多少积分
     *
     * @param int $value 金额
     * @return float|int
     */
    public function getIntegralOfValue($value = 0)
    {
        $scale = floatval($this->config['integral_scale']);

        return $scale > 0 ? round($value / $scale * 100) : 0;
    }

    /**
     * 获取卖场筛选条件
     *
     * @param $objects
     * @param string $field
     * @param int $rs_id
     * @param int $region_id
     * @return mixed
     */
    public function getWhereRsid($objects, $field = 'ru_id', $rs_id = 0, $region_id = 0)
    {
        if ($this->config['region_store_enabled']) {
            if (empty($region_id) && $rs_id > 0) {
                $region_id = RsRegion::where('rs_id', $rs_id)->value('region_id');
                $region_id = $region_id ? $region_id : 0;
            }

            if (!empty($region_id)) {
                $user_ids = MerchantsShopInformation::select('user_id')
                    ->where('region_id', $region_id)
                    ->get();
                $user_ids = $user_ids ? $user_ids->toArray() : [];

                if ($user_ids) {
                    $user_ids = collect($user_ids)->pluck('user_id')->all();
                    $user_ids = array_unique($user_ids);
                    $user_ids = array_values($user_ids);
                }

                if (!empty($user_ids)) {

                    $where = [
                        'user_ids' => $user_ids,
                        'field' => $field
                    ];

                    $objects = $objects->where(function ($query) use ($where) {
                        $query->whereIn($where['field'], $where['user_ids'])
                            ->orWhere($where['field'], 0);
                    });
                } else {
                    $objects = $objects->where($field, 0);
                }
            } else {
                $objects = $objects->where($field, 0);
            }
        }

        return $objects;
    }

    /**
     * 关联地区查询商品
     *
     * @param $res 对象
     * @param int $area_id 省份/直辖市
     * @param int $city_id 市/县
     * @return mixed
     */
    public function getAreaLinkGoods($res, $area_id = 0, $city_id = 0)
    {
        if ($this->config['open_area_goods'] == 1 && $area_id) {

            $prefix = config('database.connections.mysql.prefix');

            $where = '';
            /*if ($this->config['area_pricetype'] == 1 && $city_id) {
                $where = " AND (FIND_IN_SET('" . $city_id . "', `{$prefix}link_area_goods`.city_id))";
            }*/

            $res = $res->whereRaw("IF(`{$prefix}goods`.area_link > 0, exists(select goods_id from `{$prefix}link_area_goods` where `{$prefix}link_area_goods`.goods_id = `{$prefix}goods`.goods_id and `{$prefix}link_area_goods`.region_id = '$area_id'" . $where . "), 1)");
        }

        return $res;
    }

    /**
     * 处理oss远程图片路径
     *
     * @param array $file_arr
     * @return array
     * @throws \Exception
     */
    public function transformOssFile($file_arr = [])
    {
        if (empty($file_arr)) {
            return [];
        }

        // oss图片处理
        $oss_http = '';
        if ($this->config['open_oss'] == 1) {
            $bucket_info = $this->getBucketInfo();
            $bucket_info['endpoint'] = empty($bucket_info['endpoint']) ? $bucket_info['outside_site'] : $bucket_info['endpoint'];
            $oss_http = rtrim($bucket_info['endpoint'], '/') . '/';
        }

        foreach ($file_arr as $k => $file) {
            // oss远程图片
            if (!empty($oss_http)) {
                $file = str_replace($oss_http, '', $file);
            }

            // 本地远程图片
            if (stripos(substr($file, 0, 4), 'http') !== false) {
                $file = str_replace(url('/'), '', $file);
            }
            $file = str_replace('storage/', '', ltrim($file, '/'));
            $file_arr[$k] = $file;
        }

        return $file_arr;
    }

    /**
     * 处理编辑素材时上传保存图片
     * 配合 get_wechat_image_path 方法使用 ,将网站本地图片绝对路径地址 转换为 相对路径
     * 保存到数据库的值 为相对路径 data/attached/..... or oss完整路径
     * @param string $url
     * @return mixed|string
     */
    public function editUploadImage($url = '')
    {
        if (!empty($url)) {
            $prex_patch = rtrim($this->dscUrl(), '/') . '/';
            $url = str_replace([$prex_patch, 'storage/'], '', $url);
            $url = ltrim($url, '/');
        }

        return $url;
    }

    /**
     * 验证图片格式
     * @param string $url
     * @return bool
     */
    public function checkImageUrl($url = '')
    {
        // 验证商品图片外链格式
        $ext = strtolower(strrchr($url, '.'));
        if (substr($url, 0, 4) !== 'http' || !in_array($ext, ['.jpg', '.png', '.gif', '.jpeg'])) {
            return false;
        }

        return true;
    }

    /**
     * 验证输入的邮件地址是否合法
     *
     * @param $user_email
     * @return bool
     */
    public function isEmail($user_email)
    {
        $chars = "/^([a-z0-9+_]|\\-|\\.)+@(([a-z0-9_]|\\-)+\\.)+[a-z]{2,6}\$/i";
        if (strpos($user_email, '@') !== false && strpos($user_email, '.') !== false) {
            if (preg_match($chars, $user_email)) {
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    /**
     * 验证输入的手机号是否合法
     *
     * @param $mobile
     * @return bool
     */
    public function isMobile($mobile)
    {
        $chars = "/^(1[3-9])\d{9}$/";
        if (preg_match($chars, $mobile)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 设置伪静态链接
     *
     * @param string $initUrl 传入链接
     * @param string $params
     * @param string $append
     * @param int $page
     * @param string $keywords
     * @param int $size
     * @return bool|\Illuminate\Contracts\Routing\UrlGenerator|string
     */
    public function setRewriteUrl($initUrl = '', $params = '', $append = '', $page = 0, $keywords = '', $size = 0)
    {
        $url = false;
        $rewrite = intval($this->dscConfig('rewrite'));
        $baseUrl = basename($initUrl);
        $urlArr = explode('?', $baseUrl);

        if ($rewrite && !empty($urlArr[0]) && strpos($urlArr[0], '.php')) {
            //程序名
            $app = str_replace('.php', '', $urlArr[0]);

            //取id值
            @parse_str($urlArr[1], $queryArr);
            if (isset($queryArr['id'])) {
                $id = intval($queryArr['id']);
            }

            //链接中包含id
            if (!empty($id)) {
                //判断id类型
                switch ($app) {
                    case 'history_list':
                        $idType = ['cid' => $id];
                        break;
                    case 'category':
                        $idType = ['cid' => $id];
                        break;
                    case 'goods':
                        $idType = ['gid' => $id];
                        break;
                    case 'presale':
                        $idType = ['presaleid' => $id];
                        break;
                    case 'brand':
                        $idType = ['bid' => $id];
                        break;
                    case 'brandn':
                        $idType = ['bid' => $id];
                        break;
                    case 'article_cat':
                        $idType = ['acid' => $id];
                        break;
                    case 'article':
                        $idType = ['aid' => $id];
                        break;
                    case 'merchants':
                        $idType = ['mid' => $id];
                        break;
                    case 'merchants_index':
                        $idType = ['urid' => $id];
                        break;
                    case 'group_buy':
                        $idType = ['gbid' => $id];
                        break;
                    case 'seckill':
                        $idType = ['secid' => $id];
                        break;
                    case 'auction':
                        $idType = ['gbid' => $id];
                        break;
                    case 'snatch':
                        $idType = ['sid' => $id];
                        break;
                    case 'exchange':
                        $idType = ['cid' => $id];
                        break;
                    case 'exchange_goods':
                        $idType = ['gid' => $id];
                        break;
                    case 'gift_gard':
                        $idType = ['cid' => $id];
                        break;
                    default:
                        $idType = ['id' => ''];
                        break;
                }
            } //链接中不含id
            else {
                switch ($app) {
                    case 'index':
                        $idType = null;
                        break;
                    case 'brand':
                        $idType = null;
                        break;
                    case 'brandn':
                        $idType = null;
                        break;
                    case 'group_buy':
                        $idType = null;
                        break;
                    case 'seckill':
                        $idType = null;
                        break;
                    case 'auction':
                        $idType = null;
                        break;
                    case 'package':
                        $idType = null;
                        break;
                    case 'activity':
                        $idType = null;
                        break;
                    case 'snatch':
                        $idType = null;
                        break;
                    case 'exchange':
                        $idType = null;
                        break;
                    case 'store_street':
                        $idType = null;
                        break;
                    case 'presale':
                        $idType = null;
                        break;
                    case 'categoryall':
                        $idType = null;
                        break;
                    case 'merchants':
                        $idType = null;
                        break;
                    case 'merchants_index':
                        $idType = null;
                        break;
                    case 'message':
                        $idType = null;
                        break;
                    case 'wholesale':
                        $idType = null;
                        break;
                    case 'gift_gard':
                        $idType = null;
                        break;
                    case 'history_list':
                        $idType = null;
                        break;
                    case 'merchants_steps':
                        $idType = null;
                        break;
                    case 'merchants_steps_site':
                        $idType = null;
                        break;
                    default:
                        $idType = ['id' => ''];
                        break;
                }
            }

            //rewrite
            if ($idType == null) {
                $url = $this->dscUrl($app . '.html', config('app.url'));
            } else {
                if (strpos($initUrl, 'keywords=') !== false) {
                    $url = $initUrl;
                } else {
                    $params = empty($params) ? $idType : $params;
                    $url = $this->buildUri($app, $params, $append, $page, $keywords, $size);
                }
            }
        }

        if ($url) {
            return $url;
        } else {
            if ((strpos($initUrl, 'http://') === false && strpos($initUrl, 'https://') === false)) {
                return $this->dscUrl($initUrl, config('app.url'));
            } else {
                return $initUrl;
            }
        }
    }

    /**
     * 转化对象数组
     *
     * @param $order
     * @return array|bool|\mix|string
     */
    public function getStrArray1($order)
    {
        $arr = [];
        foreach ($order as $key => $row) {
            $row = explode("@", $row);
            $arr[$row[0]] = $row[1];
        }

        $arr = json_encode($arr, JSON_UNESCAPED_UNICODE);
        $arr = dsc_decode($arr);

        return $arr;
    }

    /**
     * 转化数组
     *
     * @param $id
     * @return array
     */
    public function getStrArray2($id)
    {
        $arr = [];
        if ($id) {
            foreach ($id as $key => $row) {
                if ($row) {
                    $row = explode("-", $row);
                    $arr[$row[0]] = $row[1];
                }
            }
        }

        return $arr;
    }

    /**
     * 店铺地址
     *
     * @param string $url
     * @param int $cid
     * @param int $bid
     * @param string $keyword
     * @param int $price_min
     * @param int $price_max
     * @param string $filter_attr
     * @param int $page
     * @param string $sort
     * @param string $order
     * @param int $is_domain
     * @return string
     */
    private function merchantsStoreUrl($url = '', $cid = 0, $bid = 0, $keyword = '', $price_min = 0, $price_max = 0, $filter_attr = '', $page = 0, $sort = '', $order = '', $is_domain = 0)
    {
        $rewrite = $this->config['rewrite'] ?? 0;
        $rewrite = intval($rewrite);

        $uri = '';
        if ($rewrite) {
            if (!empty($cid)) {
                $uri .= '-c' . $cid;
            }
            if (!empty($bid)) {
                $uri .= '-b' . $bid;
            }
            if (!empty($keyword)) {
                $uri .= '-keyword' . $keyword;
            }
            if ($price_min > 0) {
                $uri .= '-min' . $price_min;
            }
            if ($price_max > 0) {
                $uri .= '-max' . $price_max;
            }
            if (!empty($filter_attr)) {
                $uri .= '-attr' . $filter_attr;
            }
            if (!empty($page)) {
                $uri .= '-' . $page;
            }
            if (!empty($sort)) {
                $uri .= '-' . $sort;
            }
            if (!empty($order)) {
                $uri .= '-' . $order;
            }

            if ($is_domain == 1) {
                $uri = ltrim($uri, '-');
            }
        } else {
            if (!empty($cid)) {
                $uri .= '&amp;id=' . $cid;
            }

            if (!empty($bid)) {
                $uri .= '&amp;brand=' . $bid;
            }
            if (!empty($keyword)) {
                $uri .= '&amp;keyword=' . $keyword;
            }

            if ($price_min > 0) {
                $uri .= '&amp;price_min=' . $price_min;
            }

            if ($price_max > 0) {
                $uri .= '&amp;price_max=' . $price_max;
            }

            if (!empty($filter_attr)) {
                $uri .= '&amp;filter_attr=' . $filter_attr;
            }

            if (!empty($page)) {
                $uri .= '&amp;page=' . $page;
            }
            if (!empty($sort)) {
                $uri .= '&amp;sort=' . $sort;
            }
            if (!empty($order)) {
                $uri .= '&amp;order=' . $order;
            }

            if ($is_domain == 1) {
                $uri = ltrim($uri, '&amp;');
            }
        }

        return $url . $uri;
    }

    /**
     * 店铺二级域名
     *
     * @param int $seller_id
     * @param array $param
     * @return bool|\Illuminate\Contracts\Routing\UrlGenerator|string
     * @throws \Exception
     */
    public function sellerUrl($seller_id = 0, $param = [])
    {
        $url = parent::sellerDomain($seller_id);

        if (empty($url)) {
            $param['urid'] = $seller_id;
            $url = $this->buildUri('merchants_store', $param);
        } else {

            $rewrite = $this->config['rewrite'] ?? 0;
            $rewrite = intval($rewrite);

            $url = rtrim($url, '/') . '/';

            if (!empty($param)) {
                if ($rewrite == 0) {
                    $url .= config('app.store_param') . "?";
                }
            }

            $cid = $param['cid'] ?? 0;
            $bid = $param['bid'] ?? 0;
            $keyword = $param['keyword'] ?? '';
            $price_min = $param['price_min'] ?? 0;
            $price_max = $param['price_max'] ?? 0;
            $filter_attr = $param['filter_attr'] ?? '';
            $page = $param['page'] ?? 0;
            $sort = $param['sort'] ?? '';
            $order = $param['order'] ?? '';

            $url = $this->merchantsStoreUrl($url, $cid, $bid, $keyword, $price_min, $price_max, $filter_attr, $page, $sort, $order, 1);

            if (!empty($param)) {
                if ($rewrite > 0) {
                    $url .= ".html";
                }
            }
        }

        return $url;
    }
}
