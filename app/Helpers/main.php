<?php

use App\Libraries\Pager;
use App\Models\ArticleCat;
use App\Models\Category;
use App\Models\Comment;
use App\Models\Keywords;
use App\Models\PresaleCat;
use App\Models\Searchengine;
use App\Models\SellerShopinfo;
use App\Models\Sessions;
use App\Models\Single;
use App\Models\Stats;
use App\Models\Tag;
use App\Models\Template;
use App\Models\UserRank;
use App\Models\Users;
use App\Models\Vote;
use App\Models\VoteOption;
use App\Models\WholesaleCat;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\CommonRepository;
use App\Repositories\Common\DscRepository;
use App\Services\Article\ArticleCommonService;
use App\Services\Article\ArticleService;
use App\Services\Comment\CommentService;
use App\Services\Common\AreaService;
use App\Services\Common\TemplateGoodsService;
use App\Services\Friend\FriendLinkService;
use App\Services\Goods\GoodsCommentService;
use App\Services\Merchant\MerchantCommonService;
use App\Services\Navigator\NavigatorService;
use App\Services\Order\OrderGoodsService;
use App\Services\User\UserBonusService;

/**
 *  获取用户信息数组
 *
 * @access  public
 * @param
 *
 * @return array        $user       用户信息数组
 */
function get_user_info($id = 0)
{
    if ($id == 0) {
        $id = session('user_id', 0);
    }

    $user = Users::select(['user_id', 'email', 'user_name', 'user_money', 'mobile_phone', 'pay_points', 'rank_points', 'nick_name', 'user_rank'])
        ->where('user_id', $id);

    $user = app(BaseRepository::class)->getToArrayFirst($user);

    $bonus = app(UserBonusService::class)->getUserBonus($id);

    if ($user) {
        /* 更新会员等级 */
        if ($user['user_rank'] != session('user_rank')) {
            $rank_row = UserRank::select(['rank_id', 'discount'])->where('rank_id', $user['user_rank']);
            $rank_row = app(BaseRepository::class)->getToArrayFirst($rank_row);

            if ($rank_row) {
                $user['discount'] = $rank_row['discount'] / 100.00;
            } else {
                $user['discount'] = 1;
            }

            Sessions::where('userid', $user['user_id'])->where('adminid', 0)->update(['user_rank' => $user['user_rank'], 'discount' => $user['discount']]);
        }

        $user['username'] = $user['user_name'];
        if ($user['username'] != session('user_name')) {

            /* 是否邮箱 */
            $is_email = app(CommonRepository::class)->getMatchEmail(session('user_name'));

            /* 是否手机 */
            $is_phone = app(CommonRepository::class)->getMatchPhone(session('user_name'));

            if ($is_email) {
                $user['username'] = $user['email'];
            } elseif ($is_phone) {
                $user['username'] = $user['mobile_phone'];
            }
        }

        $user['payPoints'] = $user['pay_points'];
        $user['userMoney'] = $user['user_money'];

        $user['nick_name'] = !empty($user['nick_name']) ? $user['nick_name'] : $user['username'];

        $user['user_points'] = $user['pay_points'] . $GLOBALS['_CFG']['integral_name'];
        $user['user_money'] = price_format($user['user_money'], false);
        $user['user_bonus'] = price_format($bonus['bonus_value'], false);
    }

    return $user;
}

//获取当前页面action by wu
function get_page_action()
{
    //获取请求字符串
    $query_string = request()->server('QUERY_STRING');
    if (!empty($query_string)) {
        //拆分为请求数组
        $query_arr = explode('&', $query_string);
        foreach ($query_arr as $key => $val) {
            //拆分为数据对
            $val_arr = explode('=', $val);
            if ($val_arr[0] == 'act') {
                $GLOBALS['smarty']->assign('act', $val_arr[1]);
                if (!empty($GLOBALS['_LANG'][$val_arr[1]])) {
                    return $GLOBALS['_LANG'][$val_arr[1]];
                }
            }
        }
    }
    return '';
}

/**
 * 取得当前位置和页面标题
 *
 * @param int $cat 分类编号（只有商品及分类、文章及分类用到）
 * @param string $str 商品名、文章标题或其他附加的内容（无链接）
 * @param array $strArr
 * @param string $url
 * @param int $ru_id
 * @return array
 * @throws Exception
 */
function assign_ur_here($cat = 0, $str = '', $strArr = [], $url = '', $ru_id = 0)
{
    /* 初始数据 by wu */
    $data = ['head' => null, 'body' => null, 'foot' => null];
    $activity_title = '';

    /* 判断是否重写，取得文件名 */
    $cur_url = basename(PHP_SELF);
    if (intval($GLOBALS['_CFG']['rewrite'])) {
        $filename = strpos($cur_url, '-') ? substr($cur_url, 0, strpos($cur_url, '-')) : substr($cur_url, 0, -4);
    } else {
        $filename = substr($cur_url, 0, -4);
    }

    $ur_here = '';
    /* 初始化“页面标题”和“当前位置” */

    $page_title = $GLOBALS['_CFG']['shop_title'];
    if (!in_array($filename, ['category', 'goods', 'single_sun', 'brand', 'presale'])) {
        $ur_here = '<span>' . '<a href=".">' . lang('common.home') . '</a>' . '</span>';
        $data['head'] = lang('common.home'); //头部 by wu
    }

    if (empty($cat)) {
        $ur_here = '<span>' . '<a href=".">' . lang('common.home') . '</a>' . '</span>';
        $data['head'] = lang('common.home'); //头部 by wu
    }

    $cat_arr = '';

    /* 根据文件名分别处理中间的部分 */
    if ($filename != 'index') {
        /* 处理有分类的 */
        if (in_array($filename, ['category', 'goods', 'category_discuss', 'article_cat', 'article', 'brand', 'single_sun', 'store_street', 'presale', 'group_buy', 'exchange', 'seckill', 'snatch', 'wholesale_goods'])) {
            /* 商品分类或商品 */
            if ('category' == $filename || 'goods' == $filename || 'category_discuss' == $filename || 'brand' == $filename || 'single_sun' == $filename || 'presale' == $filename || 'group_buy' == $filename || 'exchange' == $filename || 'seckill' == $filename || 'snatch' == $filename || 'wholesale_goods' == $filename) {
                if ($cat > 0) {
                    if ($filename == 'presale') {
                        $cat_arr = get_presale_parent_cats($cat);

                        $key = 'cid';
                        $type = 'presale';
                    } elseif ($filename == 'wholesale_goods') {
                        $cat_arr = get_wholesale_parent_cats($cat);
                        $key = 'cid';
                        $type = 'wholesale_cat';
                    } else {
                        $cat_arr = get_parent_cats($cat);
                        $key = 'cid';
                        $type = 'category';
                    }
                } else {
                    $cat_arr = [];
                }
            } /* 文章分类或文章 */
            elseif ('article_cat' == $filename || 'article' == $filename) {
                if ($cat > 0) {
                    $cat_arr = get_article_parent_cats($cat);

                    $key = 'acid';
                    $type = 'article_cat';
                } else {
                    $cat_arr = [];
                }
            }

            /* 循环分类 */
            if (!empty($cat_arr)) {
                krsort($cat_arr);

                foreach ($cat_arr as $kval => $val) {
                    $page_title = htmlspecialchars($val['cat_name']) . '_' . $page_title;
                    $args = [$key => $val['cat_id']];

                    if ($type == 'presale') {
                        $args['act'] = 'category';
                    }

                    if ($type == 'article_cat') {
                        $ur_here .= '<span class="arrow">></span>' . '<span> <a href="' . app(DscRepository::class)->buildUri($type, $args, $val['cat_name']) . '">' . htmlspecialchars($val['cat_name']) . '</a>' . '</span>';
                    }

                    if (!(isset($val['parent_id']) && $val['parent_id'] == 0)) {
                        $ur_here .= '<span class="arrow">></span>'; //by wang
                        $ur_here .= '<span class="breadcrumb-item ziji">';
                        $ur_here .= '<span class="filter-tag"><a href="' . app(DscRepository::class)->buildUri($type, $args, $val['cat_name']) . '">' . $val['cat_name'] . '</a><i class="sc-icon-right"></i></span>';
                        $cat_arr[$kval]['url'] = app(DscRepository::class)->buildUri($type, $args, $val['cat_name']); //url by wu
                        if (isset($val['cat_tree']) && $val['cat_tree']) {
                            $ur_here .= '<div class="dorpdown-layer"><div class="dd-spacer"></div><div class="dorpdown-content-wrap">';

                            $ur_here .= "<ul>";
                            foreach ($val['cat_tree'] as $ckey => $crow) {
                                $ur_here .= '<li><a href="' . app(DscRepository::class)->buildUri($type, ['cid' => $crow['cat_id']], $crow['cat_name']) . '" title="' . $crow['cat_name'] . '">' . $crow['cat_name'] . '</a></li>';
                                $cat_arr[$kval]['cat_tree'][$ckey]['url'] = app(DscRepository::class)->buildUri($type, ['cid' => $crow['cat_id']], $crow['cat_name']); //url by wu
                            }
                            $ur_here .= "</ul>";
                            $ur_here .= '</div></div>';
                        }
                        $ur_here .= '</span>';
                    }
                }
            }

            $data['body'] = $cat_arr; //中间 by wu
        } /* 处理无分类的 */
        else {
            /* 团购 */
            if ('group_buy' == $filename) {
                $activity_title = lang('common.group_buy');
                $page_title = lang('common.group_buy_goods') . '_' . $page_title;
                $args = ['gbid' => '0'];
                $ur_here .= '<span class="arrow">></span>' . '<span> <a href="group_buy.php">' . lang('common.group_buy_goods') . '</a>' . '</span>';
            } /* 拍卖 */
            elseif ('auction' == $filename) {
                $activity_title = lang('common.auction');
                $page_title = lang('common.auction') . '_' . $page_title;
                $args = ['auid' => '0'];
                $ur_here .= '<span class="arrow">></span>' . '<span> <a href="auction.php">' .
                    lang('common.auction') . '</a>' . '</span>';
            } elseif ('store_street' == $filename) {
                $page_title = lang('common.store_street') . '_' . $page_title;
                $args = ['auid' => '0'];
                $ur_here .= '<span class="arrow">></span>' . '<span> <a href="store_street.php">' .
                    lang('common.store_street') . '</a>' . '</span>';
            } /* 夺宝 */
            elseif ('snatch' == $filename) {
                $activity_title = lang('common.snatch');
                $page_title = lang('common.snatch') . '_' . $page_title;
                $args = ['id' => '0'];
                $ur_here .= ' <span class="arrow">></span>' . '<span><a href="snatch.php">' . lang('common.snatch_list') . '</a>' . '</span>';
            } /* 批发 */
            elseif ('wholesale' == $filename) {
                $page_title = lang('common.wholesale') . '_' . $page_title;
                $args = ['wsid' => '0'];
                $ur_here .= ' <span class="arrow">></span>' . '<span> <a href="wholesale.php">' .
                    lang('common.wholesale') . '</a>' . '</span>';
            } /* 积分兑换 */
            elseif ('exchange' == $filename) {
                $activity_title = lang('common.exchange');
                $page_title = lang('common.exchange') . '_' . $page_title;
                $args = ['wsid' => '0'];
                $ur_here .= ' <span class="arrow">></span>' . '<span> <a href="exchange.php">' .
                    lang('common.exchange') . '</a>' . '</span>';
            } /* 晒单 by guan */
            elseif ('single_sun' == $filename) {
                $page_title = lang('common.single_user') . '_' . $page_title;
                $args = ['siid' => '0'];
                $ur_here .= " <code>&gt;</code> ";
                $ur_here .= '<a href="single_sun.php">' .
                    lang('common.single_user') . '</a>';
            } // 优惠活动
            elseif ('activity' == $filename) {
                $page_title = lang('common.shopping_activity') . '_' . $page_title;
                $args = ['auid' => '0'];
                $ur_here .= '<span class="arrow">></span>' . '<span> <a href="activity.php">' .
                    lang('common.shopping_activity') . '</a>' . '</span>';
            } // 预售活动
            elseif ('presale' == $filename) {
                $activity_title = lang('common.presell');
                $page_title = lang('common.shopping_activity') . '_' . $page_title;
                $args = ['auid' => '0'];
                $ur_here .= '<span class="arrow">></span>' . '<span> <a href="presale.php">' .
                    lang('common.presell') . '</a>' . '</span>';
            }
            //ecmoban模板堂 --zhuo start
            /* 礼品卡 */
            elseif ('gift_gard' == $filename) {
                $activity_title = lang('common.gift_card_exchange');
                $page_title = lang('common.gift_card_exchange') . '_' . $page_title;
                $args = ['wsid' => '0'];
                $ur_here .= ' <code>&gt;</code> <a href="gift_gard.php">' .
                    lang('common.gift_card_exchange') . '</a>';
            }
            //ecmoban模板堂 --zhuo end

            /* 其他的在这里补充 */
            //专题
            elseif ('topic' == $filename) {
                $page_title = lang('common.topic') . '_' . $page_title;
                $args = ['id' => '0'];
                $ur_here .= '<span class="arrow">></span>' . '<span> <a href="topic.php">' .
                    lang('common.topic') . '</a>' . '</span>';
            }
        }
    }

    /* 处理最后一部分 */
    if (!empty($str)) {
        $filename_arr = ['group_buy', 'seckill', 'auction', 'auction_list', 'store_street', 'snatch', 'wholesale', 'exchange', 'single_sun', 'activity', 'presale', 'gift_gard', 'topic', 'article_cat', 'wholesale_cat'];

        if (!in_array($filename, $filename_arr)) {
            $action = get_page_action();
        }

        if (!empty($action)) {
            $page_title = $action;
        }

        $page_title = $str . '_' . $page_title;

        $str = !empty($url) ? "<a href='" . $url . "'>" . $str . "</a>" : $str;
        $ur_here .= '<span class="arrow">></span>' . ' <span class="finish">' . $str . '</span>';
        $data['foot'] = $str; //尾部 by wu
    }

    if ($strArr) {
        if (count($strArr) > 1) {
            foreach ($strArr as $key => $row) {
                $strArr[$key] = "<span>" . $row . "</span>";
            }

            $ur_here .= '<span class="arrow">></span>';
            $implode_str = implode(',', $strArr);
            $strArr = str_replace(',', "<span class='arrow'>></span>", $implode_str);
            $ur_here .= $strArr;
        } else {
            $implode_str = implode(',', $strArr);
            $strArr = '<span class="arrow">></span>' . $implode_str;
            $ur_here .= '<span>' . $strArr . '</span>';
        }
    }

    /* 页面名称 by wu */
    $GLOBALS['smarty']->assign('filename', $filename);

    /* 页面数据 by wu */
    $GLOBALS['smarty']->assign('data', $data);
    $GLOBALS['smarty']->assign('activity_title', $activity_title);

    /* 返回值 */
    return ['title' => $page_title, 'ur_here' => $ur_here];
}

/**
 * 获得指定分类的所有上级分类
 *
 * @param int $cat
 * @return array|bool|\Illuminate\Cache\CacheManager|mixed
 * @throws Exception
 */
function get_parent_cats($cat = 0)
{
    if ($cat == 0) {
        return [];
    }

    $cache_name = 'get_parent_cats_' . $cat;
    $cats = cache($cache_name);

    if (is_null($cats)) {
        $catList = parentsList($cat);

        $cats = [];
        if ($catList) {

            $list = Category::where('is_show', 1)->whereIn('cat_id', $catList);

            $list = $list->with([
                'catList'
            ]);

            $list = $list->orderBy('cat_id', 'desc');

            $list = $list->get();

            $list = $list ? $list->toArray() : [];

            foreach ($list as $key => $value) {
                $cats[$key] = $value;
                $cats[$key]['cat_tree'] = $value['cat_list'];

                unset($cats[$key]['cat_list']);
            }
        }

        cache()->forever($cache_name, $cats);
    }

    return $cats;
}

/**
 * 获得指定分类的所有上级分类
 *
 * @param int $cat
 * @return array|bool|\Illuminate\Cache\CacheManager|mixed
 * @throws Exception
 */
function get_presale_parent_cats($cat = 0)
{
    if ($cat == 0) {
        return [];
    }

    $cache_name = 'get_presale_parent_cats_' . $cat;
    $cats = cache($cache_name);

    if (is_null($cats)) {
        $catList = parentsList($cat, 'presale_cat');

        $cats = [];
        if ($catList) {

            $list = PresaleCat::whereIn('cat_id', $catList);

            $list = $list->with([
                'catList'
            ]);

            $list = $list->orderBy('cat_id', 'desc');

            $list = $list->get();

            $list = $list ? $list->toArray() : [];

            foreach ($list as $key => $value) {
                $cats[$key] = $value;
                $cats[$key]['cat_tree'] = $value['cat_list'];

                unset($cats[$key]['cat_list']);
            }
        }

        cache()->forever($cache_name, $cats);
    }

    return $cats;
}

/**
 * 获得指定分类的所有上级分类
 *
 * @param $cat
 * @return array|bool|\Illuminate\Cache\CacheManager|mixed
 * @throws Exception
 */
function get_wholesale_parent_cats($cat)
{
    if ($cat == 0) {
        return [];
    }

    $cache_name = 'get_wholesale_parent_cats_' . $cat;
    $cats = cache($cache_name);

    if (is_null($cats)) {
        $catList = parentsList($cat, 'wholesale_cat');

        $cats = [];
        if ($catList) {

            $list = PresaleCat::whereIn('cat_id', $catList);

            $list = $list->with([
                'catList'
            ]);

            $list = $list->orderBy('cat_id', 'desc');

            $list = $list->get();

            $list = $list ? $list->toArray() : [];

            foreach ($list as $key => $value) {
                $cats[$key] = $value;
                $cats[$key]['cat_tree'] = $value['cat_list'];

                unset($cats[$key]['cat_list']);
            }
        }

        cache()->forever($cache_name, $cats);
    }

    return $cats;
}


/**
 * 递归获取父级ID
 *
 * @param int $cat
 * @param string $type
 * @return array
 */
function parentsList($cat = 0, $type = 'category')
{
    if ($type == 'presale_cat') {
        $arr = PresaleCat::select('cat_id', 'parent_id')->where('cat_id', $cat);
    } elseif ($type == 'wholesale_cat') {
        $arr = WholesaleCat::select('cat_id', 'parent_id')->where('cat_id', $cat);
    } else {
        $arr = Category::select('cat_id', 'parent_id')->where('is_show', 1)->where('cat_id', $cat);
    }

    $arr = $arr->with([
        'catParentList'
    ]);

    $arr = $arr->get();

    $arr = $arr ? $arr->toArray() : [];

    $arr = app(BaseRepository::class)->getFlatten($arr);

    $list = [];
    if ($arr) {
        foreach ($arr as $val) {
            if (is_numeric($val)) {
                $list[] = $val;
            }
        }
    }

    $list = $list ? array_unique($list) : [];

    return $list;
}

/**
 * 根据提供的数组编译成页面标题
 *
 * @access  public
 * @param string $type 类型
 * @param array $arr 分类数组
 * @return  string
 */
function build_pagetitle($arr, $type = 'category')
{
    $str = '';

    foreach ($arr as $val) {
        $str .= htmlspecialchars($val['cat_name']) . '_';
    }

    return $str;
}

/**
 * 根据提供的数组编译成当前位置
 *
 * @access  public
 * @param string $type 类型
 * @param array $arr 分类数组
 * @return  void
 */
function build_urhere($arr, $type = 'category')
{
    krsort($arr);

    $str = '';
    foreach ($arr as $val) {
        switch ($type) {
            case 'category':
            case 'brand':
                $args = ['cid' => $val['cat_id']];
                break;
            case 'article_cat':
                $args = ['acid' => $val['cat_id']];
                break;
        }

        $str .= ' <code>&gt;</code> <span>' . htmlspecialchars($val['cat_name']) . '</span>';
    }

    return $str;
}

/**
 * 获得指定页面的动态内容
 *
 * @param $tmp
 * @param int $warehouse_id
 * @param int $area_id
 * @param int $area_city
 */
function assign_dynamic($tmp, $warehouse_id = 0, $area_id = 0, $area_city = 0)
{
    if ($GLOBALS['_CFG']['openvisual'] == 0) {

        $res = Template::where('filename', $tmp)
            ->where('type', '>', 0)
            ->where('remarks', '')
            ->where('theme', $GLOBALS['_CFG']['template']);

        $res = app(BaseRepository::class)->getToArrayGet($res);

        if ($res) {
            foreach ($res as $row) {
                $row['type'] = 3;

                switch ($row['type']) {
                    case 1:
                        /* 分类下的商品 */
                        $GLOBALS['smarty']->assign('goods_cat_' . $row['id'], []);
                        break;
                    case 2:

                        /* 品牌的商品 */
                        $brand_goods = app(TemplateGoodsService::class)->getAssignBrandGoods($row['id'], 0, $row['number'], $warehouse_id, $area_id, $area_city);

                        $GLOBALS['smarty']->assign('brand_goods_' . $row['id'], $brand_goods['goods']);
                        $GLOBALS['smarty']->assign('goods_brand_' . $row['id'], $brand_goods['brand']);
                        break;
                    case 3:
                        /* 文章列表 */
                        $cat_articles = app(ArticleService::class)->getAssignArticles($row['id'], $row['number']);

                        $GLOBALS['smarty']->assign('articles_cat_' . $row['id'], $cat_articles['cat']);
                        $GLOBALS['smarty']->assign('articles_' . $row['id'], $cat_articles['arr']);
                        break;
                }
            }
        }
    }
}

/**
 * 创建分页信息
 *
 * @access  public
 * @param string $app 程序名称，如category
 * @param string $cat 分类ID
 * @param string $record_count 记录总数
 * @param string $size 每页记录数
 * @param string $sort 排序类型
 * @param string $order 排序顺序
 * @param string $page 当前页
 * @param string $keywords 查询关键字
 * @param string $brand 品牌
 * @param string $price_min 最小价格
 * @param string $price_max 最高价格
 * @return  void
 */
function assign_pager(
    $app,
    $cat,
    $record_count,
    $size,
    $sort,
    $order,
    $page = 1,
    $keywords = '',
    $brand = 0,
    $price_min = 0,
    $price_max = 0,
    $display_type = 'list',
    $filter_attr = '',
    $url_format = '',
    $sch_array = '',
    $merchant_id = 0,
    $keyword = '',
    $ubrand = '',
    $act = '',
    $ship = '',
    $self = '',
    $have = '',
    $mbid = 0
)
{
    $sch = ['keywords' => $keywords,
        'sort' => $sort,
        'order' => $order,
        'cat' => $cat,
        'brand' => $brand,
        'price_min' => $price_min,
        'price_max' => $price_max,
        'filter_attr' => $filter_attr,
        'display' => e($display_type),
        'urid' => $merchant_id,
        'keyword' => $keyword,
        'ubrand' => $ubrand
    ];

    $page = intval($page);
    if ($page < 1) {
        $page = 1;
    }

    $page_count = $record_count > 0 ? intval(ceil($record_count / $size)) : 1;

    $pager['page'] = $page;
    $pager['size'] = $size;
    $pager['sort'] = $sort;
    $pager['order'] = $order;
    $pager['record_count'] = $record_count;
    $pager['page_count'] = $page_count;
    $pager['display'] = e($display_type);
    $pager['ship'] = $ship;
    $pager['self'] = $self;
    $pager['have'] = $have;
    $pager['mbid'] = $mbid;
    $uri_args = [];

    switch ($app) {
        case 'category':
            $uri_args = ['cid' => $cat, 'bid' => $brand, 'ubrand' => $ubrand, 'price_min' => $price_min, 'price_max' => $price_max, 'filter_attr' => $filter_attr, 'sort' => $sort, 'order' => $order, 'display' => $display_type, 'ship' => $ship, 'self' => $self, 'have' => $have];
            break;
        case 'merchants_store': //ecmoban模板堂 --zhuo
            $uri_args = ['cid' => $cat, 'urid' => $merchant_id, 'bid' => $brand, 'keyword' => $keyword, 'price_min' => $price_min, 'price_max' => $price_max, 'filter_attr' => $filter_attr, 'sort' => $sort, 'order' => $order, 'display' => $display_type];
            break;
        case 'merchants_store_shop': //ecmoban模板堂 --zhuo
            $uri_args = ['cid' => $cat, 'urid' => $merchant_id, 'bid' => $brand, 'keyword' => $keyword, 'price_min' => $price_min, 'price_max' => $price_max, 'filter_attr' => $filter_attr, 'sort' => $sort, 'order' => $order, 'display' => $display_type];
            break;
        case 'article_cat':
            $uri_args = ['acid' => $cat, 'sort' => $sort, 'order' => $order];
            break;
        case 'brand':
            $uri_args = ['cid' => $cat, 'bid' => $brand, 'mbid' => $mbid, 'price_min' => $price_min, 'price_max' => $price_max, 'sort' => $sort, 'order' => $order, 'display' => $display_type, 'ship' => $ship, 'self' => $self, 'have' => $have];
            break;
        case 'brandn':
            $uri_args = ['cid' => $cat, 'bid' => $brand, 'sort' => $sort, 'order' => $order, 'display' => $display_type, 'act' => $act];
            break;
        case 'search':
            $uri_args = ['cid' => $cat, 'bid' => $brand, 'sort' => $sort, 'order' => $order];
            break;
        case 'exchange':
            $uri_args = ['cid' => $cat, 'integral_min' => $price_min, 'integral_max' => $price_max, 'sort' => $sort, 'order' => $order, 'display' => $display_type];
            break;
        case 'history_list':
            $uri_args = ['cid' => $cat];//ecmoban模板堂 --zhuo   浏览列表插件
            break;

        //ecmoban模板堂 zhuo start
        case 'gift_gard':
            $uri_args = ['cid' => $cat, 'sort' => $sort, 'order' => $order];
            break;
        //ecmoban模板堂 zhuo end
    }
    /* 分页样式 */
    $pager['styleid'] = isset($GLOBALS['_CFG']['page_style']) ? intval($GLOBALS['_CFG']['page_style']) : 0;

    $page_prev = ($page > 1) ? $page - 1 : 1;
    $page_next = ($page < $page_count) ? $page + 1 : $page_count;
    if ($pager['styleid'] == 0) {
        if (!empty($url_format)) {
            $pager['page_first'] = $url_format . 1;
            $pager['page_prev'] = $url_format . $page_prev;
            $pager['page_next'] = $url_format . $page_next;
            $pager['page_last'] = $url_format . $page_count;
        } else {
            $pager['page_first'] = app(DscRepository::class)->buildUri($app, $uri_args, '', 1, $keywords);
            $pager['page_prev'] = app(DscRepository::class)->buildUri($app, $uri_args, '', $page_prev, $keywords);
            $pager['page_next'] = app(DscRepository::class)->buildUri($app, $uri_args, '', $page_next, $keywords);
            $pager['page_last'] = app(DscRepository::class)->buildUri($app, $uri_args, '', $page_count, $keywords);
        }
        $pager['array'] = [];

        for ($i = 1; $i <= $page_count; $i++) {
            $pager['array'][$i] = $i;
        }
    } else {
        $_pagenum = 10;     // 显示的页码
        $_offset = 2;       // 当前页偏移值
        $_from = $_to = 0;  // 开始页, 结束页
        if ($_pagenum > $page_count) {
            $_from = 1;
            $_to = $page_count;
        } else {
            $_from = $page - $_offset;
            $_to = $_from + $_pagenum - 1;
            if ($_from < 1) {
                $_to = $page + 1 - $_from;
                $_from = 1;
                if ($_to - $_from < $_pagenum) {
                    $_to = $_pagenum;
                }
            } elseif ($_to > $page_count) {
                $_from = $page_count - $_pagenum + 1;
                $_to = $page_count;
            }
        }
        if (!empty($url_format)) {
            $pager['page_first'] = ($page - $_offset > 1 && $_pagenum < $page_count) ? $url_format . 1 : '';
            $pager['page_prev'] = ($page > 1) ? $url_format . $page_prev : '';
            $pager['page_next'] = ($page < $page_count) ? $url_format . $page_next : '';
            $pager['page_last'] = ($_to < $page_count) ? $url_format . $page_count : '';
            $pager['page_kbd'] = ($_pagenum < $page_count) ? true : false;
            $pager['page_number'] = [];
            for ($i = $_from; $i <= $_to; ++$i) {
                $pager['page_number'][$i] = $url_format . $i;
            }
        } else {
            $pager['page_first'] = ($page - $_offset > 1 && $_pagenum < $page_count) ? app(DscRepository::class)->buildUri($app, $uri_args, '', 1, $keywords) : '';
            $pager['page_prev'] = ($page > 1) ? app(DscRepository::class)->buildUri($app, $uri_args, '', $page_prev, $keywords) : '';
            $pager['page_next'] = ($page < $page_count) ? app(DscRepository::class)->buildUri($app, $uri_args, '', $page_next, $keywords) : '';
            $pager['page_last'] = ($_to < $page_count) ? app(DscRepository::class)->buildUri($app, $uri_args, '', $page_count, $keywords) : '';
            $pager['page_kbd'] = ($_pagenum < $page_count) ? true : false;
            $pager['page_number'] = [];
            for ($i = $_from; $i <= $_to; ++$i) {
                $pager['page_number'][$i] = app(DscRepository::class)->buildUri($app, $uri_args, '', $i, $keywords);
            }
        }
    }

    if (!empty($sch_array)) {
        $pager['search'] = $sch_array;
    } else {
        $pager['search']['category'] = $cat;
        foreach ($sch as $key => $row) {
            $pager['search'][$key] = $row;
        }
    }

    $GLOBALS['smarty']->assign('pager', $pager);
}

/**
 *  生成给pager.lbi赋值的数组
 *
 * @access  public
 * @param string $url 分页的链接地址(必须是带有参数的地址，若不是可以伪造一个无用参数)
 * @param array $param 链接参数 key为参数名，value为参数值
 * @param int $record 记录总数量
 * @param int $page 当前页数
 * @param int $size 每页大小
 *
 * @return  array       $pager
 */
function get_pager($url, $param, $record_count, $page = 1, $size = 10)
{
    $size = intval($size);
    if ($size < 1) {
        $size = 10;
    }

    $page = intval($page);
    if ($page < 1) {
        $page = 1;
    }

    $record_count = intval($record_count);

    $page_count = $record_count > 0 ? intval(ceil($record_count / $size)) : 1;
    if ($page > $page_count) {
        $page = $page_count;
    }
    /* 分页样式 */
    $pager['styleid'] = isset($GLOBALS['_CFG']['page_style']) ? intval($GLOBALS['_CFG']['page_style']) : 0;

    $page_prev = ($page > 1) ? $page - 1 : 1;
    $page_next = ($page < $page_count) ? $page + 1 : $page_count;

    /* 将参数合成url字串 */
    $param_url = '?';
    foreach ($param as $key => $value) {
        $param_url .= $key . '=' . $value . '&';
    }

    $pager['url'] = $url;
    $pager['start'] = ($page - 1) * $size;
    $pager['page'] = $page;
    $pager['size'] = $size;
    $pager['record_count'] = $record_count;
    $pager['page_count'] = $page_count;

    if ($pager['styleid'] == 0) {
        $pager['page_first'] = $url . $param_url . 'page=1';
        $pager['page_prev'] = $url . $param_url . 'page=' . $page_prev;
        $pager['page_next'] = $url . $param_url . 'page=' . $page_next;
        $pager['page_last'] = $url . $param_url . 'page=' . $page_count;
        $pager['array'] = [];
        for ($i = 1; $i <= $page_count; $i++) {
            $pager['array'][$i] = $i;
        }
    } else {
        $_pagenum = 10;     // 显示的页码
        $_offset = 2;       // 当前页偏移值
        $_from = $_to = 0;  // 开始页, 结束页
        if ($_pagenum > $page_count) {
            $_from = 1;
            $_to = $page_count;
        } else {
            $_from = $page - $_offset;
            $_to = $_from + $_pagenum - 1;
            if ($_from < 1) {
                $_to = $page + 1 - $_from;
                $_from = 1;
                if ($_to - $_from < $_pagenum) {
                    $_to = $_pagenum;
                }
            } elseif ($_to > $page_count) {
                $_from = $page_count - $_pagenum + 1;
                $_to = $page_count;
            }
        }
        $url_format = $url . $param_url . 'page=';
        $pager['page_first'] = ($page - $_offset > 1 && $_pagenum < $page_count) ? $url_format . 1 : '';
        $pager['page_prev'] = ($page > 1) ? $url_format . $page_prev : '';
        $pager['page_next'] = ($page < $page_count) ? $url_format . $page_next : '';
        $pager['page_last'] = ($_to < $page_count) ? $url_format . $page_count : '';
        $pager['page_kbd'] = ($_pagenum < $page_count) ? true : false;
        $pager['page_number'] = [];
        for ($i = $_from; $i <= $_to; ++$i) {
            $pager['page_number'][$i] = $url_format . $i;
        }
    }
    $pager['search'] = $param;

    return $pager;
}

/**
 * 调用调查内容
 *
 * @access  public
 * @param integer $id 调查的编号
 * @return  array
 */
function get_vote($id = '')
{
    /* 随机取得一个调查的主题 */
    $time = gmtime();
    $vote_arr = Vote::where('start_time', '<=', $time)->where('end_time', '>=', $time);

    if (empty($id)) {
        $vote_arr = $vote_arr->where('vote_id', $id);
    }

    $vote_arr = $vote_arr->orderByRaw('RAND()');
    $vote_arr = app(BaseRepository::class)->getToArrayFirst($vote_arr);

    if ($vote_arr) {


        /* 总票数 */
        $option_num = VoteOption::where('vote_id', $vote_arr['vote_id'])->count();

        /* 通过调查的ID,查询调查选项 */
        $res = VoteOption::where('vote_id', $vote_arr['vote_id'])
            ->with('getVote');

        $res = $res->orderBy('option_order')
            ->orderBy('option_id', 'desc');

        $res = app(BaseRepository::class)->getToArrayGet($res);

        $arr = [];
        $count = 100;
        if ($res) {
            foreach ($res as $idx => $row) {
                $row = app(BaseRepository::class)->getArrayMerge($row, $row['get_vote']);
                $arr[$row['vote_id']] = $row;

                if ($option_num > 0 && $idx == count($res) - 1) {
                    $percent = $count;
                } else {
                    $percent = ($row['vote_count'] > 0 && $option_num > 0) ? round(($row['option_count'] / $option_num) * 100) : 0;

                    $count -= $percent;
                }
                $arr[$row['vote_id']]['options'][$row['option_id']]['percent'] = $percent;

                $arr[$row['vote_id']]['vote_id'] = $row['vote_id'];
                $arr[$row['vote_id']]['vote_name'] = $row['vote_name'];
                $arr[$row['vote_id']]['can_multi'] = $row['can_multi'];
                $arr[$row['vote_id']]['vote_count'] = $row['vote_count'];

                $arr[$row['vote_id']]['options'][$row['option_id']]['option_id'] = $row['option_id'];
                $arr[$row['vote_id']]['options'][$row['option_id']]['option_name'] = $row['option_name'];
                $arr[$row['vote_id']]['options'][$row['option_id']]['option_count'] = $row['option_count'];
            }
        }


        $vote_arr['vote_id'] = (!empty($vote_arr['vote_id'])) ? $vote_arr['vote_id'] : '';

        $vote = ['id' => $vote_arr['vote_id'], 'content' => $arr];

        return $vote;
    }
}

/**
 * 获得浏览器名称和版本
 *
 * @access  public
 * @return  string
 */
function get_user_browser()
{
    if (!request()->server('HTTP_USER_AGENT')) {
        return '';
    }

    $agent = request()->server('HTTP_USER_AGENT');
    $browser = '';
    $browser_ver = '';

    if (preg_match('/MSIE\s([^\s|;]+)/i', $agent, $regs)) {
        $browser = 'Internet Explorer';
        $browser_ver = $regs[1];
    } elseif (preg_match('/FireFox\/([^\s]+)/i', $agent, $regs)) {
        $browser = 'FireFox';
        $browser_ver = $regs[1];
    } elseif (preg_match('/Maxthon/i', $agent, $regs)) {
        $browser = '(Internet Explorer ' . $browser_ver . ') Maxthon';
        $browser_ver = '';
    } elseif (preg_match('/Opera[\s|\/]([^\s]+)/i', $agent, $regs)) {
        $browser = 'Opera';
        $browser_ver = $regs[1];
    } elseif (preg_match('/OmniWeb\/(v*)([^\s|;]+)/i', $agent, $regs)) {
        $browser = 'OmniWeb';
        $browser_ver = $regs[2];
    } elseif (preg_match('/Netscape([\d]*)\/([^\s]+)/i', $agent, $regs)) {
        $browser = 'Netscape';
        $browser_ver = $regs[2];
    } elseif (preg_match('/safari\/([^\s]+)/i', $agent, $regs)) {
        $browser = 'Safari';
        $browser_ver = $regs[1];
    } elseif (preg_match('/NetCaptor\s([^\s|;]+)/i', $agent, $regs)) {
        $browser = '(Internet Explorer ' . $browser_ver . ') NetCaptor';
        $browser_ver = $regs[1];
    } elseif (preg_match('/Lynx\/([^\s]+)/i', $agent, $regs)) {
        $browser = 'Lynx';
        $browser_ver = $regs[1];
    }

    if (!empty($browser)) {
        return addslashes($browser . ' ' . $browser_ver);
    } else {
        return 'Unknow browser';
    }
}

/**
 * 判断是否为搜索引擎蜘蛛
 *
 * @access  public
 * @return  string
 */
function is_spider($record = true)
{
    static $spider = null;

    if ($spider !== null) {
        return $spider;
    }

    if (!request()->server('HTTP_USER_AGENT')) {
        $spider = '';

        return '';
    }

    $searchengine_bot = [
        'googlebot',
        'mediapartners-google',
        'baiduspider+',
        'msnbot',
        'yodaobot',
        'yahoo! slurp;',
        'yahoo! slurp china;',
        'iaskspider',
        'sogou web spider',
        'sogou push spider'
    ];

    $searchengine_name = [
        'GOOGLE',
        'GOOGLE ADSENSE',
        'BAIDU',
        'MSN',
        'YODAO',
        'YAHOO',
        'Yahoo China',
        'IASK',
        'SOGOU',
        'SOGOU'
    ];

    $spider = strtolower(request()->server('HTTP_USER_AGENT'));

    if ($searchengine_bot) {
        foreach ($searchengine_bot as $key => $value) {
            if (strpos($spider, $value) !== false) {
                $spider = $searchengine_name[$key];

                if ($record === true) {
                    $other = [
                        'date' => local_date('Y-m-d'),
                        'searchengine' => $spider,
                        'count' => 1
                    ];
                    Searchengine::updateOrCreate($other, ['count' => 1]);
                }

                return $spider;
            }
        }
    }

    $spider = '';

    return '';
}

/**
 * 获得客户端的操作系统
 *
 * @access  private
 * @return  void
 */
function get_os()
{
    if (!request()->server('HTTP_USER_AGENT')) {
        return 'Unknown';
    }

    $agent = strtolower(request()->server('HTTP_USER_AGENT'));
    $os = '';

    if (strpos($agent, 'win') !== false) {
        if (strpos($agent, 'nt 5.1') !== false) {
            $os = 'Windows XP';
        } elseif (strpos($agent, 'nt 5.2') !== false) {
            $os = 'Windows 2003';
        } elseif (strpos($agent, 'nt 5.0') !== false) {
            $os = 'Windows 2000';
        } elseif (strpos($agent, 'nt 6.0') !== false) {
            $os = 'Windows Vista';
        } elseif (strpos($agent, 'nt') !== false) {
            $os = 'Windows NT';
        } elseif (strpos($agent, 'win 9x') !== false && strpos($agent, '4.90') !== false) {
            $os = 'Windows ME';
        } elseif (strpos($agent, '98') !== false) {
            $os = 'Windows 98';
        } elseif (strpos($agent, '95') !== false) {
            $os = 'Windows 95';
        } elseif (strpos($agent, '32') !== false) {
            $os = 'Windows 32';
        } elseif (strpos($agent, 'ce') !== false) {
            $os = 'Windows CE';
        }
    } elseif (strpos($agent, 'linux') !== false) {
        $os = 'Linux';
    } elseif (strpos($agent, 'unix') !== false) {
        $os = 'Unix';
    } elseif (strpos($agent, 'sun') !== false && strpos($agent, 'os') !== false) {
        $os = 'SunOS';
    } elseif (strpos($agent, 'ibm') !== false && strpos($agent, 'os') !== false) {
        $os = 'IBM OS/2';
    } elseif (strpos($agent, 'mac') !== false && strpos($agent, 'pc') !== false) {
        $os = 'Macintosh';
    } elseif (strpos($agent, 'powerpc') !== false) {
        $os = 'PowerPC';
    } elseif (strpos($agent, 'aix') !== false) {
        $os = 'AIX';
    } elseif (strpos($agent, 'hpux') !== false) {
        $os = 'HPUX';
    } elseif (strpos($agent, 'netbsd') !== false) {
        $os = 'NetBSD';
    } elseif (strpos($agent, 'bsd') !== false) {
        $os = 'BSD';
    } elseif (strpos($agent, 'osf1') !== false) {
        $os = 'OSF1';
    } elseif (strpos($agent, 'irix') !== false) {
        $os = 'IRIX';
    } elseif (strpos($agent, 'freebsd') !== false) {
        $os = 'FreeBSD';
    } elseif (strpos($agent, 'teleport') !== false) {
        $os = 'teleport';
    } elseif (strpos($agent, 'flashget') !== false) {
        $os = 'flashget';
    } elseif (strpos($agent, 'webzip') !== false) {
        $os = 'webzip';
    } elseif (strpos($agent, 'offline') !== false) {
        $os = 'offline';
    } else {
        $os = 'Unknown';
    }

    return $os;
}

/**
 * 统计访问信息
 *
 * @access  public
 * @return  void
 */
function visit_stats()
{
    if (isset($GLOBALS['_CFG']['visit_stats']) && $GLOBALS['_CFG']['visit_stats'] == 'off') {
        return;
    }
    $time = gmtime();

    $ecsCookie = request()->cookie('ECS');

    /* 检查客户端是否存在访问统计的cookie */
    $visit_times = (isset($ecsCookie['visit_times']) && !empty($ecsCookie['visit_times'])) ? intval($ecsCookie['visit_times']) + 1 : 1;
    cookie()->queue('ECS[visit_times]', $visit_times, 60 * 24 * 365);

    $browser = get_user_browser();
    $os = get_os();
    $ip = app(DscRepository::class)->dscIp();

    //修改获取地区方式 by wu
    $area_info = app(AreaService::class)->ipAreaName();

    if (isset($area_info['city']) && $area_info['city']) {
        $area = $area_info['city'];
    } else {
        $area = $area_info['province'] ?? '';
    }

    /* 语言 */
    if (request()->server('HTTP_ACCEPT_LANGUAGE')) {
        $pos = strpos(request()->server('HTTP_ACCEPT_LANGUAGE'), ';');
        $lang = addslashes(($pos !== false) ? substr(request()->server('HTTP_ACCEPT_LANGUAGE'), 0, $pos) : request()->server('HTTP_ACCEPT_LANGUAGE'));
    } else {
        $lang = '';
    }

    /* 来源 */
    if (request()->server('HTTP_REFERER') && strlen(request()->server('HTTP_REFERER')) > 9) {
        $pos = strpos(request()->server('HTTP_REFERER'), '/', 9);
        if ($pos !== false) {
            $domain = substr(request()->server('HTTP_REFERER'), 0, $pos);
            $path = substr(request()->server('HTTP_REFERER'), $pos);

            /* 来源关键字 */
            if (!empty($domain) && !empty($path)) {
                save_searchengine_keyword($domain, $path);
            }
        } else {
            $domain = $path = '';
        }
    } else {
        $domain = $path = '';
    }

    $other = [
        'ip_address' => $ip,
        'visit_times' => $visit_times,
        'browser' => $browser,
        'system' => $os,
        'language' => $lang,
        'area' => $area,
        'referer_domain' => addslashes($domain),
        'referer_path' => addslashes($path),
        'access_url' => addslashes(PHP_SELF),
        'access_time' => $time
    ];
    Stats::insert($other);
}

/**
 * 保存搜索引擎关键字
 *
 * @access  public
 * @return  void
 */
function save_searchengine_keyword($domain, $path)
{
    if (strpos($domain, 'google.com.tw') !== false && preg_match('/q=([^&]*)/i', $path, $regs)) {
        $searchengine = 'GOOGLE TAIWAN';
        $keywords = urldecode($regs[1]); // google taiwan
    }
    if (strpos($domain, 'google.cn') !== false && preg_match('/q=([^&]*)/i', $path, $regs)) {
        $searchengine = 'GOOGLE CHINA';
        $keywords = urldecode($regs[1]); // google china
    }
    if (strpos($domain, 'google.com') !== false && preg_match('/q=([^&]*)/i', $path, $regs)) {
        $searchengine = 'GOOGLE';
        $keywords = urldecode($regs[1]); // google
    } elseif (strpos($domain, 'baidu.') !== false && preg_match('/wd=([^&]*)/i', $path, $regs)) {
        $searchengine = 'BAIDU';
        $keywords = urldecode($regs[1]); // baidu
    } elseif (strpos($domain, 'baidu.') !== false && preg_match('/word=([^&]*)/i', $path, $regs)) {
        $searchengine = 'BAIDU';
        $keywords = urldecode($regs[1]); // baidu
    } elseif (strpos($domain, '114.vnet.cn') !== false && preg_match('/kw=([^&]*)/i', $path, $regs)) {
        $searchengine = 'CT114';
        $keywords = urldecode($regs[1]); // ct114
    } elseif (strpos($domain, 'iask.com') !== false && preg_match('/k=([^&]*)/i', $path, $regs)) {
        $searchengine = 'IASK';
        $keywords = urldecode($regs[1]); // iask
    } elseif (strpos($domain, 'soso.com') !== false && preg_match('/w=([^&]*)/i', $path, $regs)) {
        $searchengine = 'SOSO';
        $keywords = urldecode($regs[1]); // soso
    } elseif (strpos($domain, 'sogou.com') !== false && preg_match('/query=([^&]*)/i', $path, $regs)) {
        $searchengine = 'SOGOU';
        $keywords = urldecode($regs[1]); // sogou
    } elseif (strpos($domain, 'so.163.com') !== false && preg_match('/q=([^&]*)/i', $path, $regs)) {
        $searchengine = 'NETEASE';
        $keywords = urldecode($regs[1]); // netease
    } elseif (strpos($domain, 'yodao.com') !== false && preg_match('/q=([^&]*)/i', $path, $regs)) {
        $searchengine = 'YODAO';
        $keywords = urldecode($regs[1]); // yodao
    } elseif (strpos($domain, 'zhongsou.com') !== false && preg_match('/word=([^&]*)/i', $path, $regs)) {
        $searchengine = 'ZHONGSOU';
        $keywords = urldecode($regs[1]); // zhongsou
    } elseif (strpos($domain, 'search.tom.com') !== false && preg_match('/w=([^&]*)/i', $path, $regs)) {
        $searchengine = 'TOM';
        $keywords = urldecode($regs[1]); // tom
    } elseif (strpos($domain, 'live.com') !== false && preg_match('/q=([^&]*)/i', $path, $regs)) {
        $searchengine = 'MSLIVE';
        $keywords = urldecode($regs[1]); // MSLIVE
    } elseif (strpos($domain, 'tw.search.yahoo.com') !== false && preg_match('/p=([^&]*)/i', $path, $regs)) {
        $searchengine = 'YAHOO TAIWAN';
        $keywords = urldecode($regs[1]); // yahoo taiwan
    } elseif (strpos($domain, 'cn.yahoo.') !== false && preg_match('/p=([^&]*)/i', $path, $regs)) {
        $searchengine = 'YAHOO CHINA';
        $keywords = urldecode($regs[1]); // yahoo china
    } elseif (strpos($domain, 'yahoo.') !== false && preg_match('/p=([^&]*)/i', $path, $regs)) {
        $searchengine = 'YAHOO';
        $keywords = urldecode($regs[1]); // yahoo
    } elseif (strpos($domain, 'msn.com.tw') !== false && preg_match('/q=([^&]*)/i', $path, $regs)) {
        $searchengine = 'MSN TAIWAN';
        $keywords = urldecode($regs[1]); // msn taiwan
    } elseif (strpos($domain, 'msn.com.cn') !== false && preg_match('/q=([^&]*)/i', $path, $regs)) {
        $searchengine = 'MSN CHINA';
        $keywords = urldecode($regs[1]); // msn china
    } elseif (strpos($domain, 'msn.com') !== false && preg_match('/q=([^&]*)/i', $path, $regs)) {
        $searchengine = 'MSN';
        $keywords = urldecode($regs[1]); // msn
    }

    if (!empty($keywords)) {
        $gb_search = ['YAHOO CHINA', 'TOM', 'ZHONGSOU', 'NETEASE', 'SOGOU', 'SOSO', 'IASK', 'CT114', 'BAIDU'];
        if (EC_CHARSET == 'utf-8' && in_array($searchengine, $gb_search)) {
            $keywords = dsc_iconv('GBK', 'UTF8', $keywords);
        }
        if (EC_CHARSET == 'gbk' && !in_array($searchengine, $gb_search)) {
            $keywords = dsc_iconv('UTF8', 'GBK', $keywords);
        }

        $time = local_date('Y-m-d');
        $keywords = addslashes($keywords);

        $count = Keywords::where('date', $time)
            ->where('searchengine', $searchengine)
            ->where('keyword', $keywords)
            ->count();

        if ($count <= 0) {
            $other = [
                'date' => $time,
                'searchengine' => $searchengine,
                'keyword' => $keywords,
                'count' => 1
            ];
            Keywords::insert($other);
        } else {
            Keywords::where('date', $time)
                ->where('searchengine', $searchengine)
                ->where('keyword', $keywords)
                ->increment('count');
        }
    }
}

/**
 * 获得指定用户、商品的所有标记
 *
 * @access  public
 * @param integer $goods_id
 * @param integer $user_id
 * @return  array
 */
function get_tags($goods_id = 0, $user_id = 0)
{
    $res = Tag::selectRaw("tag_id, user_id, tag_words, COUNT(tag_id) AS tag_count")
        ->whereRaw(1);

    if ($goods_id > 0) {
        $res = $res->where('goods_id', $goods_id);
    }

    if ($user_id > 0) {
        $res = $res->where('user_id', $user_id);
    }

    $res = $res->groupBy('tag_words');

    $res = app(BaseRepository::class)->getToArrayGet($res);

    return $res;
}

/**
 * 获取指定主题某个模板的主题的动态模块
 *
 * @access  public
 * @param string $theme 模板主题
 * @param string $tmp 模板名称
 *
 * @return []
 */
function get_dyna_libs($theme, $tmp)
{
    $tmp_arr = explode('.', $tmp);
    $ext = end($tmp_arr);
    $tmp = basename($tmp, ".$ext");

    $res = Template::where('theme', $theme)
        ->where('filename', $tmp)
        ->where('type', '>', 0)
        ->where('remarks', '')
        ->orderByRaw("region, library, sort_order asc");

    $res = app(BaseRepository::class)->getToArrayGet($res);

    $dyna_libs = [];
    if ($res) {
        foreach ($res as $row) {
            $dyna_libs[$row['region']][$row['library']][] = [
                'id' => $row['id'],
                'number' => $row['number'],
                'type' => $row['type']
            ];
        }
    }


    return $dyna_libs;
}

/**
 * 替换动态模块
 *
 * @access  public
 * @param string $matches 匹配内容
 *
 * @return string        结果
 */
function dyna_libs_replace($matches)
{
    $key = '/' . $matches[1];

    if ($row = array_shift($GLOBALS['libs'][$key])) {
        $str = '';
        switch ($row['type']) {
            case 1:
                // 分类的商品
                $str = '{assign var="cat_goods" value=$cat_goods_' . $row['id'] . '}{assign var="goods_cat" value=$goods_cat_' . $row['id'] . '}';
                break;
            case 2:
                // 品牌的商品
                $str = '{assign var="brand_goods" value=$brand_goods_' . $row['id'] . '}{assign var="goods_brand" value=$goods_brand_' . $row['id'] . '}';
                break;
            case 3:
                // 文章列表
                $str = '{assign var="articles" value=$articles_' . $row['id'] . '}{assign var="articles_cat" value=$articles_cat_' . $row['id'] . '}';
                break;
            case 4:
                //广告位
                $str = '{assign var="ads_id" value=' . $row['id'] . '}{assign var="ads_num" value=' . $row['number'] . '}';
                break;
        }
        return $str . $matches[0];
    } else {
        return $matches[0];
    }
}

/**
 * 处理上传文件，并返回上传图片名(上传失败时返回图片名为空）
 *
 * @access  public
 * @param array $upload $_FILES 数组
 * @param array $type 图片所属类别，即data目录下的文件夹名
 *
 * @return string               上传图片名
 */
function upload_file($upload, $type)
{
    if (!empty($upload['tmp_name'])) {
        $ftype = check_file_type($upload['tmp_name'], $upload['name'], '|png|jpg|jpeg|gif|doc|xls|txt|zip|ppt|pdf|rar|docx|xlsx|pptx|php|');
        if (!empty($ftype)) {
            $name = date('Ymd');
            for ($i = 0; $i < 6; $i++) {
                $name .= chr(mt_rand(97, 122));
            }

            $name = session('user_id') . '_' . $name . '.' . $ftype;

            $target = storage_public(DATA_DIR . '/' . $type . '/' . $name);
            if (!move_upload_file($upload['tmp_name'], $target)) {
                $GLOBALS['err']->add($GLOBALS['_LANG']['upload_file_error'], 1);

                return false;
            } else {
                return $name;
            }
        } else {
            $GLOBALS['err']->add($GLOBALS['_LANG']['upload_file_type'], 1);

            return false;
        }
    } else {
        $GLOBALS['err']->add($GLOBALS['_LANG']['upload_file_error']);
        return false;
    }
}

/**
 * 显示一个提示信息
 *
 * @access  public
 * @param string $content
 * @param string $link
 * @param string $href
 * @param string $type 信息类型：warning, error, info
 * @param string $auto_redirect 是否自动跳转
 * @return  void
 */
function show_message($content, $links = '', $hrefs = '', $type = 'info', $auto_redirect = true)
{
    assign_template();

    $msg['content'] = $content;
    if (is_array($links) && is_array($hrefs)) {
        if (!empty($links) && count($links) == count($hrefs)) {
            foreach ($links as $key => $val) {
                $msg['url_info'][$val] = $hrefs[$key];
            }
            $msg['back_url'] = $hrefs['0'];
        }
    } else {
        $link = empty($links) ? $GLOBALS['_LANG']['back_up_page'] : $links;
        $href = empty($hrefs) ? 'javascript:history.back()' : $hrefs;
        $msg['url_info'][$link] = $href;
        $msg['back_url'] = $href;
    }

    $msg['type'] = $type;
    $position = assign_ur_here(0, lang('common.sys_msg'));
    $GLOBALS['smarty']->assign('page_title', $position['title']);   // 页面标题
    $GLOBALS['smarty']->assign('ur_here', $position['ur_here']); // 当前位置

    if (is_null($GLOBALS['smarty']->get_template_vars('helps'))) {
        $GLOBALS['smarty']->assign('helps', app(ArticleCommonService::class)->getShopHelp()); // 网店帮助
    }

    $categories_pro = get_category_tree_leve_one();
    $GLOBALS['smarty']->assign('categories_pro', $categories_pro); // 分类树加强版

    $GLOBALS['smarty']->assign('auto_redirect', $auto_redirect);
    $GLOBALS['smarty']->assign('message', $msg);

    return $GLOBALS['smarty']->display('message.dwt');
}

/**
 * 将一个形如+10, 10, -10, 10%的字串转换为相应数字，并返回操作符号
 *
 * @access  public
 * @param string      str     要格式化的数据
 * @param char        operate 操作符号，只能返回‘+’或‘*’;
 * @return  float       value   浮点数
 */
function parse_rate_value($str, &$operate)
{
    $operate = '+';
    $is_rate = false;

    $str = trim($str);
    if (empty($str)) {
        return 0;
    }
    if ($str[strlen($str) - 1] == '%') {
        $value = floatval($str);
        if ($value > 0) {
            $operate = '*';

            return $value / 100;
        } else {
            return 0;
        }
    } else {
        return floatval($str);
    }
}

/**
 * 查询评论内容
 *
 * @access  public
 * @params  integer     $id
 * @params  integer     $type
 * @params  integer     $page
 * @return  array
 */
function assign_comment($id, $type, $page = 1, $cmtType = 0)
{
    $tag = [];
    $idStr = '"' . $id . "|" . $cmtType . '"';

    /* 取得评论列表 */
    $count = Comment::where('id_value', $id)
        ->where('comment_type', $type)
        ->where('status', 1)
        ->where('parent_id', 0);

    if ($cmtType == 1) { //好评
        $count = $count->whereIn('comment_rank', [5, 4]);
    } elseif ($cmtType == 2) { //中评
        $count = $count->whereIn('comment_rank', [3, 2]);
    } elseif ($cmtType == 3) { //差评
        $count = $count->where('comment_rank', 1);
    }

    $count = $count->count();

    $size = !empty($GLOBALS['_CFG']['comments_number']) ? $GLOBALS['_CFG']['comments_number'] : 5;

    $pagerParams = [
        'total' => $count,
        'listRows' => $size,
        'id' => $idStr,
        'page' => $page,
        'funName' => 'gotoPage',
        'pageType' => 1
    ];
    $comment = new Pager($pagerParams);
    $pager = $comment->fpage([0, 4, 5, 6, 9]);

    $res = Comment::where('id_value', $id)
        ->where('comment_type', $type)
        ->where('status', 1)
        ->where('parent_id', 0);

    if ($cmtType == 1) { //好评
        $res = $res->whereIn('comment_rank', [5, 4]);
    } elseif ($cmtType == 2) { //中评
        $res = $res->whereIn('comment_rank', [3, 2]);
    } elseif ($cmtType == 3) { //差评
        $res = $res->where('comment_rank', 1);
    }

    $res = $res->with([
        'getOrderGoods' => function ($query) {
            $query->select('rec_id', 'goods_attr');
        }
    ]);

    $res = $res->orderBy('add_time', 'desc');

    $start = ($page - 1) * $size;

    if ($start > 0) {
        $res = $res->skip($start);
    }

    if ($size > 0) {
        $res = $res->take($size);
    }

    $res = app(BaseRepository::class)->getToArrayGet($res);

    $ids = '';
    $arr = [];
    if ($res) {
        $CommentLib = app(CommentService::class);
        foreach ($res as $key => $row) {
            $row['user_name'] = $row['user_name'] ? setAnonymous($row['user_name']) : ''; //处理用户名 by wu
            $ids .= $key > 0 ? "," . $row['comment_id'] : $row['comment_id'];
            $arr[$row['comment_id']]['id'] = $row['comment_id'];
            $arr[$row['comment_id']]['email'] = $row['email'];
            $arr[$row['comment_id']]['username'] = $row['user_name'];
            $arr[$row['comment_id']]['user_id'] = $row['user_id'];
            $arr[$row['comment_id']]['id_value'] = $row['id_value'];
            $arr[$row['comment_id']]['useful'] = $row['useful'];

            $user_picture = Users::where('user_id', $row['user_id'])->value('user_picture');

            $arr[$row['comment_id']]['user_picture'] = $user_picture;
            $arr[$row['comment_id']]['content'] = nl2br(str_replace('\n', '<br />', htmlspecialchars($row['content'])));
            $arr[$row['comment_id']]['rank'] = $row['comment_rank'];
            $arr[$row['comment_id']]['server'] = $row['comment_server'];
            $arr[$row['comment_id']]['delivery'] = $row['comment_delivery'];
            $arr[$row['comment_id']]['add_time'] = local_date($GLOBALS['_CFG']['time_format'], $row['add_time']);
            $arr[$row['comment_id']]['buy_goods'] = app(OrderGoodsService::class)->getUserBuyGoodsOrder($row['id_value'], $row['user_id'], $row['order_id']);

            //商品印象
            if ($row['goods_tag']) {
                $row['goods_tag'] = explode(",", $row['goods_tag']);
                foreach ($row['goods_tag'] as $key => $val) {
                    $tag[$key]['txt'] = $val;
                    //印象数量
                    $tag[$key]['num'] = app(GoodsCommentService::class)->commentGoodsTagNum($row['id_value'], $val);
                }
                $arr[$row['comment_id']]['goods_tag'] = $tag;
            }

            $reply = app(CommentService::class)->getReplyList($row['id_value'], $row['comment_id']);
            $arr[$row['comment_id']]['reply_list'] = $reply['reply_list'];
            $arr[$row['comment_id']]['reply_count'] = $reply['reply_count'];
            $arr[$row['comment_id']]['reply_size'] = $reply['reply_size'];
            $arr[$row['comment_id']]['reply_pager'] = $reply['reply_pager'];

            $imgOther = [
                'goods_id' => $row['id_value'],
                'comment_id' => $row['comment_id']
            ];
            $img_list = $CommentLib->getCommentImgList($imgOther);

            $arr[$row['comment_id']]['img_list'] = $img_list;
            $arr[$row['comment_id']]['img_cont'] = count($img_list);

            $arr[$row['comment_id']]['user_picture'] = get_image_path($arr[$row['comment_id']]['user_picture']);

            $arr[$row['comment_id']]['goods_attr'] = $row['get_order_goods'] ? $row['get_order_goods']['goods_attr'] : '';
        }
    }


    /* 取得已有回复的评论 */
    if ($ids) {
        $ids = !is_array($ids) ? explode(",", $ids) : $ids;
        $res = Comment::where('user_id', 0)
            ->whereIn('parent_id', $ids);

        $res = app(BaseRepository::class)->getToArrayGet($res);

        if ($res) {
            foreach ($res as $row) {
                $arr[$row['parent_id']]['re_content'] = nl2br(str_replace('\n', '<br />', htmlspecialchars($row['content'])));
                $arr[$row['parent_id']]['re_add_time'] = local_date($GLOBALS['_CFG']['time_format'], $row['add_time']);
                $arr[$row['parent_id']]['re_email'] = $row['email'];
                $arr[$row['parent_id']]['re_username'] = $row['user_name'];
                $arr[$row['parent_id']]['shop_name'] = app(MerchantCommonService::class)->getShopName($row['ru_id'], 1);
            }
        }
    }

    $cmt = ['comments' => $arr, 'pager' => $pager, 'count' => $count, 'size' => $size];

    return $cmt;
}


/**
 * 查询评论内容 //晒单评价
 *
 * @access  public
 * @params  integer     $id
 * @params  integer     $type
 * @params  integer     $page
 * @return  array
 */
function assign_comments_single($id, $type, $page = 1)
{
    $CommentLib = app(CommentService::class);

    /* 取得评论列表 */
    $count = Single::where('goods_id', $id)->count();
    $size = !empty($GLOBALS['_CFG']['comments_number']) ? $GLOBALS['_CFG']['comments_number'] : 5;

    $page_count = ($count > 0) ? intval(ceil($count / $size)) : 1;

    $res = Single::where('goods_id', $id);

    $res = $res->orderBy('addtime', 'desc');

    $start = ($page - 1) * $size;

    if ($start > 0) {
        $res = $res->skip($start);
    }

    if ($size > 0) {
        $res = $res->take($size);
    }

    $res = app(BaseRepository::class)->getToArrayGet($res);

    $arr = [];
    $ids = '';
    if ($res) {
        foreach ($res as $row) {
            $row['content'] = $row['single_description'];

            $ids .= $ids ? ",$row[single_id]" : $row['single_id'];
            $arr[$row['single_id']]['single_id'] = $row['single_id'];
            $arr[$row['single_id']]['user_name'] = $row['user_name'];
            $arr[$row['single_id']]['comment_id'] = $row['comment_id'];

            $user_picture = Users::where('user_id', $row['user_id'])->value('user_picture') .
                $arr[$row['single_id']]['content'] = str_replace('\r\n', '<br />', htmlspecialchars($row['content']));

            $where = [
                'goods_id' => $id,
                'comment_id' => $row['comment_id']
            ];
            $img_list = $CommentLib->getCommentImgList($where);

            $arr[$row['single_id']]['img_list'] = $img_list;
            $arr[$row['single_id']]['img_cont'] = count($img_list);

            $useful = Comment::where('comment_id', $row['comment_id'])->value('useful');
            $reply_count = Comment::where('parent_id', $row['comment_id'])->count();
            $arr[$row['single_id']]['useful'] = $useful;
            $arr[$row['single_id']]['reply_count'] = $reply_count;

            $single_reply = assign_comments_single_reply($row['comment_id'], $type);
            $arr[$row['single_id']]['reply_comment'] = $single_reply['reply_comments'];
            $arr[$row['single_id']]['reply_paper'] = $single_reply['reply_paper'];
        }
    }


    /* 分页样式 */
    $pager['page'] = $page;
    $pager['size'] = $size;
    $pager['record_count'] = $count;
    $pager['page_count'] = $page_count;
    $pager['page_first'] = "javascript:single_gotoPage(1,$id,$type)";
    $pager['page_prev'] = $page > 1 ? "javascript:single_gotoPage(" . ($page - 1) . ",$id,$type)" : 'javascript:;';
    $pager['page_next'] = $page < $page_count ? 'javascript:single_gotoPage(' . ($page + 1) . ",$id,$type)" : 'javascript:;';
    $pager['page_last'] = $page < $page_count ? 'javascript:single_gotoPage(' . $page_count . ",$id,$type)" : 'javascript:;';

    $cmt = ['comments' => $arr, 'pager' => $pager];
    return $cmt;
}

/**
 * 查询评论内容 //晒单评价
 *
 * @access  public
 * @params  integer     $id
 * @params  integer     $type
 * @params  integer     $page
 * @return  array
 */
function assign_comments_single_reply($parent_id = 0, $type = 0, $page = 1)
{
    $count = Comment::where('parent_id', $parent_id)->where('single_id', '>', 0)->count();

    $size = 5;

    $pagerParams = [
        'total' => $count,
        'listRows' => $size,
        'id' => $parent_id,
        'page' => $page,
        'funName' => 'single_reply_gotoPage',
        'pageType' => 1
    ];
    $reply_comment = new Pager($pagerParams);
    $limit = $reply_comment->limit;
    $reply_paper = $reply_comment->fpage([0, 4, 5, 6, 9]);

    /* 取得评论列表 */
    $res = Comment::where('parent_id', $parent_id)->where('single_id', '>', 0)->where('status', 1)->orderBy('add_time', 'desc');

    $start = ($page - 1) * $size;

    if ($start > 0) {
        $res = $res->skip($start);
    }

    if ($size > 0) {
        $res = $res->take($size);
    }

    $res = app(BaseRepository::class)->getToArrayGet($res);

    $arr = [];
    if ($res) {
        foreach ($res as $key => $row) {
            $arr[$row['comment_id']]['comment_id'] = $row['comment_id'];
            $arr[$row['comment_id']]['user_name'] = $row['user_name'];
            $arr[$row['comment_id']]['content'] = str_replace('\r\n', '<br />', htmlspecialchars($row['content']));
            $arr[$row['comment_id']]['content'] = nl2br(str_replace('\n', '<br />', $arr[$row['comment_id']]['content']));
            $arr[$row['comment_id']]['add_time'] = local_date($GLOBALS['_CFG']['time_format'], $row['add_time']);
        }
    }

    $cmt = ['reply_comments' => $arr, 'reply_paper' => $reply_paper];

    return $cmt;
}

/**
 * @param string $cat_type
 * @param array $cat_list
 * @param int $merchant_id
 * @throws Exception
 */
function assign_template($cat_type = '', $cat_list = [], $merchant_id = 0)
{

    $GLOBALS['smarty']->assign('rewrite', $GLOBALS['_CFG']['rewrite']);
    $GLOBALS['smarty']->assign('image_width', $GLOBALS['_CFG']['image_width']);
    $GLOBALS['smarty']->assign('image_height', $GLOBALS['_CFG']['image_height']);
    $GLOBALS['smarty']->assign('points_name', $GLOBALS['_CFG']['integral_name']);
    $GLOBALS['smarty']->assign('qq', explode(',', $GLOBALS['_CFG']['qq']));
    $GLOBALS['smarty']->assign('ww', explode(',', $GLOBALS['_CFG']['ww']));
    $GLOBALS['smarty']->assign('ym', explode(',', $GLOBALS['_CFG']['ym']));
    $GLOBALS['smarty']->assign('msn', explode(',', $GLOBALS['_CFG']['msn']));
    $GLOBALS['smarty']->assign('skype', explode(',', $GLOBALS['_CFG']['skype']));
    $GLOBALS['smarty']->assign('stats_code', html_out($GLOBALS['_CFG']['stats_code']));
    $GLOBALS['smarty']->assign('copyright', sprintf(lang('common.copyright'), date('Y'), $GLOBALS['_CFG']['shop_name']));
    $GLOBALS['smarty']->assign('shop_name', $GLOBALS['_CFG']['shop_name']);
    $GLOBALS['smarty']->assign('service_email', $GLOBALS['_CFG']['service_email']);
    $GLOBALS['smarty']->assign('service_phone', $GLOBALS['_CFG']['service_phone']);
    $GLOBALS['smarty']->assign('shop_address', $GLOBALS['_CFG']['shop_address']);
    $GLOBALS['smarty']->assign('ad_reminder', $GLOBALS['_CFG']['ad_reminder']); //广告位提示设置
    $GLOBALS['smarty']->assign('ecs_version', VERSION);
    $GLOBALS['smarty']->assign('icp_number', $GLOBALS['_CFG']['icp_number']);
    $GLOBALS['smarty']->assign('username', !empty(session('user_name')) ? session('user_name') : '');

    //是否开启举报
    $GLOBALS['smarty']->assign('is_illegal', $GLOBALS['_CFG']['is_illegal']);

    //获取店铺描述，关键字
    $seller_head = [];
    if ($merchant_id > 0) {
        $seller_head = SellerShopinfo::where('ru_id', $merchant_id)->first();
        $seller_head = $seller_head ? $seller_head->toArray() : [];
    }

    //判断是否已经注册变量
    if (is_null($GLOBALS['smarty']->get_template_vars('keywords'))) {
        if (isset($seller_head['shop_keyword']) && $seller_head['shop_keyword'] != '') {
            $GLOBALS['smarty']->assign('keywords', htmlspecialchars($seller_head['shop_keyword']));
        } else {
            $GLOBALS['smarty']->assign('keywords', htmlspecialchars($GLOBALS['_CFG']['shop_keywords']));
        }
    }

    if (is_null($GLOBALS['smarty']->get_template_vars('description'))) {
        if (isset($seller_head['street_desc']) && $seller_head['street_desc'] != '') {
            $GLOBALS['smarty']->assign('description', htmlspecialchars($seller_head['street_desc']));
        } else {
            $GLOBALS['smarty']->assign('description', htmlspecialchars($GLOBALS['_CFG']['shop_desc']));
        }
    }

    $GLOBALS['smarty']->assign('shop_logo', get_image_path($GLOBALS['_CFG']['shop_logo']));
    $GLOBALS['smarty']->assign('ecjia_qrcode', get_image_path($GLOBALS['_CFG']['ecjia_qrcode']));
    $GLOBALS['smarty']->assign('ectouch_qrcode', get_image_path($GLOBALS['_CFG']['ectouch_qrcode']));
    $GLOBALS['smarty']->assign('index_down_logo', get_image_path($GLOBALS['_CFG']['index_down_logo']));
    $GLOBALS['smarty']->assign('user_login_logo', get_image_path($GLOBALS['_CFG']['user_login_logo']));
    $GLOBALS['smarty']->assign('login_logo_pic', get_image_path($GLOBALS['_CFG']['login_logo_pic']));

    $business_logo = isset($GLOBALS['_CFG']['business_logo']) && !empty($GLOBALS['_CFG']['business_logo']) ? $GLOBALS['_CFG']['business_logo'] : '';
    $GLOBALS['smarty']->assign('business_logo', get_image_path($business_logo));

    $top_cat_list = get_category_tree_leve_one();
    $GLOBALS['smarty']->assign('top_cat_list', $top_cat_list); //网站导航顶级分类
    $GLOBALS['smarty']->assign('nav_cat_model', $GLOBALS['_CFG']['nav_cat_model']); //网站导航顶级分类
    if ($GLOBALS['_CFG']['nav_cat_model']) {
        $GLOBALS['smarty']->assign('nav_cat_num', 16);
    } else {
        $GLOBALS['smarty']->assign('nav_cat_num', 7);
    }

    $navigator_list = app(NavigatorService::class)->getNavigator($cat_type, $cat_list);
    $GLOBALS['smarty']->assign('navigator_list', $navigator_list);  //自定义导航栏

    $links = app(FriendLinkService::class)->getIndexGetLinks();

    $GLOBALS['smarty']->assign('img_links', $links['img']);
    $GLOBALS['smarty']->assign('txt_links', $links['txt']);

    $partner_links = app(FriendLinkService::class)->getIndexGetLinks('partner_list');
    $GLOBALS['smarty']->assign('partner_img_links', $partner_links['img']);
    $GLOBALS['smarty']->assign('partner_txt_links', $partner_links['txt']);

    $urlHtmlKey = [
        'seckill',
        'categoryall',
        'index',
        'merchants',
        'merchants_steps',
        'merchants_steps_site',
        'presale'
    ];

    $urlHtml = app(DscRepository::class)->getUrlHtml($urlHtmlKey);

    //URL html伪静态
    $GLOBALS['smarty']->assign('url_seckill', $urlHtml['seckill']);
    $GLOBALS['smarty']->assign('url_categoryall', $urlHtml['categoryall']);
    $GLOBALS['smarty']->assign('url_index', $urlHtml['index']);
    $GLOBALS['smarty']->assign('url_merchants', $urlHtml['merchants']);
    $GLOBALS['smarty']->assign('url_merchants_steps', $urlHtml['merchants_steps']);
    $GLOBALS['smarty']->assign('url_merchants_steps_site', $urlHtml['merchants_steps_site']);
    $GLOBALS['smarty']->assign('url_presale', $urlHtml['presale']);


    $GLOBALS['smarty']->assign('url_presale_new', app(DscRepository::class)->buildUri('presale', ['act' => 'new']));
    $GLOBALS['smarty']->assign('url_business_buy', app(DscRepository::class)->buildUri('wholesale', ['act' => 'buy']));
    $GLOBALS['smarty']->assign('url_presale_advance', app(DscRepository::class)->buildUri('presale', ['act' => 'advance']));
    $GLOBALS['smarty']->assign('shop_reg_closed', $GLOBALS['_CFG']['shop_reg_closed']);
    $GLOBALS['smarty']->assign('dwt_shop_name', $GLOBALS['_CFG']['shop_name']);

    //楼层样式左侧定位导航样式
    if (isset($GLOBALS['_CFG']['floor_nav_type'])) {
        if ($GLOBALS['_CFG']['floor_nav_type'] == 1) {
            $GLOBALS['smarty']->assign('floor_nav_type', "one");
        } elseif ($GLOBALS['_CFG']['floor_nav_type'] == 2) {
            $GLOBALS['smarty']->assign('floor_nav_type', "two");
        } elseif ($GLOBALS['_CFG']['floor_nav_type'] == 3) {
            $GLOBALS['smarty']->assign('floor_nav_type', "sthree");
        } elseif ($GLOBALS['_CFG']['floor_nav_type'] == 4) {
            $GLOBALS['smarty']->assign('floor_nav_type', "four");
        }
    }

    if (!empty($GLOBALS['_CFG']['search_keywords'])) {
        $search_keywords = explode(',', trim($GLOBALS['_CFG']['search_keywords']));
    } else {
        $search_keywords = [];
    }

    $GLOBALS['smarty']->assign('searchkeywords', $search_keywords);

    $GLOBALS['smarty']->assign('user_id', session('user_id'));
}

/**
 * 获得指定文章分类的所有上级分类
 *
 * @access  public
 * @param integer $cat 分类编号
 * @return  array
 */
function get_article_parent_cats($cat)
{
    if ($cat == 0) {
        return [];
    }

    $arr = ArticleCat::get();
    $arr = $arr ? $arr->toArray() : [];

    if (empty($arr)) {
        return [];
    }

    $index = 0;
    $cats = [];

    if ($arr) {
        while (1) {
            foreach ($arr as $row) {
                if ($cat == $row['cat_id']) {
                    $cat = $row['parent_id'];

                    $cats[$index]['cat_id'] = $row['cat_id'];
                    $cats[$index]['cat_name'] = $row['cat_name'];

                    $index++;
                    break;
                }
            }

            if ($index == 0 || $cat == 0) {
                break;
            }
        }
    }

    return $cats;
}

