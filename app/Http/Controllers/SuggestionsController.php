<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Goods;
use App\Models\SearchKeyword;
use App\Models\Tag;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\DscRepository;
use App\Services\Category\CategoryService;
use App\Services\Search\SearchService;

/*
 * 搜索框提示功能
 */

class SuggestionsController extends InitController
{
    protected $categoryService;
    protected $searchService;
    protected $baseRepository;
    protected $config;
    protected $dscRepository;

    public function __construct(
        CategoryService $categoryService,
        SearchService $searchService,
        BaseRepository $baseRepository,
        DscRepository $dscRepository
    )
    {
        $this->categoryService = $categoryService;
        $this->searchService = $searchService;
        $this->baseRepository = $baseRepository;
        $this->config = $this->config();
        $this->dscRepository = $dscRepository;
    }

    public function index()
    {

        /**
         * Start
         *
         * @param $warehouse_id 仓库ID
         * @param $area_id 省份ID
         * @param $area_city 城市ID
         */
        $warehouse_id = $this->warehouseId();
        $area_id = $this->areaId();
        $area_city = $this->areaCity();
        /* End */

        $keyword = addslashes(trim(request()->input('keyword', '')));
        $category = trim(request()->input('category', 0));

        $children = [];
        if ($category == $GLOBALS['_LANG']['Template']) {
            $children = $this->categoryService->getCatListChildren(9);
        } elseif ($category == $GLOBALS['_LANG']['plugins']) {
            $children = $this->categoryService->getCatListChildren(23);
        }

        $keyword = $keyword ? addslashes($keyword) : '';

        if (empty($keyword)) {
            echo '';
        } else {
            $result = SearchKeyword::whereRaw("keyword LIKE '%" . mysql_like_quote($keyword) . "%' OR pinyin_keyword LIKE '%" . mysql_like_quote($keyword) . "%'")
                ->distinct('keyword')
                ->orderBy('count', 'desc')
                ->take(10);
            $result = $this->baseRepository->getToArrayGet($result);

            //查询分类

            $cate_res = Category::whereRaw("cat_name LIKE '%" . mysql_like_quote($keyword) . "%' OR pinyin_keyword LIKE '%" . mysql_like_quote($keyword) . "%'");

            if ($children) {
                $cate_res = $cate_res->whereIn('cat_id', $children);
            }

            $cate_res = $cate_res->take(4);

            $cate_res = $cate_res->get();

            $cate_res = $cate_res ? $cate_res->toArray() : [];

            $cat_html = '';
            if ($cate_res) {
                foreach ($cate_res as $key => $row) {
                    if ($row['parent_id'] > 0) {
                        $cat_name = Category::where('cat_id', $row['parent_id'])->value('cat_name');

                        $url = $this->dscRepository->buildUri('category', ['cid' => $row['cat_id']], $row['cat_name']);
                        if ($url == "") {
                            $url = '#';
                        }
                        $cat_html .= '<li onmouseover="_over(this);" onmouseout="_out(this);">' . "&nbsp;&nbsp;&nbsp;在<a class='cate_user' href=" . $url . " style='color:#ec5151;'>" . $cat_name . ">" . $row['cat_name'] . "</a>" . $GLOBALS['_LANG']['cat_search'] . '</li>';
                    }
                }
            }

            $html = '<ul id="suggestions_list_id"><input type="hidden" value="1" name="selectKeyOne" id="keyOne" />';
            $res_num = 0;
            $exist_keyword = [];
            if ($result) {
                foreach ($result as $row) {
                    $scws_res = scws($row['keyword']); //这里可以把关键词分词：诺基亚，耳机
                    $arr = explode(',', $scws_res);

                    /* 补充搜索条件 by wu start */
                    $insert_keyword = addslashes(trim($row['keyword']));
                    if (empty($arr[0])) {
                        $arr[0] = $insert_keyword;
                    }
                    /* 补充搜索条件 by wu end */

                    $arr_keyword = $arr;
                    $goods_ids = [];
                    if ($arr) {
                        foreach ($arr as $key => $val) {
                            $val = !empty($val) ? addslashes($val) : '';
                            if ($val) {
                                $val = mysql_like_quote(trim($val));

                                $res = Tag::where('tag_words', 'like', '%' . $val . '%')->get();
                                $res = $res ? $res->toArray() : [];

                                if ($res) {
                                    foreach ($res as $row) {
                                        $goods_ids[] = $row['goods_id'];
                                    }
                                }
                            }
                        }
                    }

                    $goods_ids = !empty($goods_ids) ? array_unique($goods_ids) : [];

                    /* 获得符合条件的商品总数 */
                    $count = $this->searchService->getSearchGoodsCount(0, 0, $children, $warehouse_id, $area_id, $area_city, 0, 0, [], [], $goods_ids, $arr_keyword);

                    /* 补充搜索条件 by wu end */

                    //如果查询的数量为空则不显示此关键词
                    if ($count <= 0) {
                        continue;
                    }

                    $keyword = preg_quote($keyword); //特殊字符自动添加转义符\
                    $keyword_style = preg_replace("/($keyword)/i", "<font style='font-weight:normal;color:#ec5151;'>$1</font>", $row['keyword']);
                    $html .= '<li onmouseover="_over(this);" title="' . $row['keyword'] . '" onmouseout="_out(this);" onClick="javascript:fill(\'' . $row['keyword'] . '\');"><div class="left-span">&nbsp;' . $keyword_style . '</div><div class="suggest_span">约' . $count . '个商品</div></li>';
                    $res_num++;
                    $exist_keyword[] = $row['keyword'];
                }
            }

            if (isset($cat_html) && $cat_html != "") {
                $html .= $cat_html;
                $html .= '<li style="height:1px; overflow:hidden; border-bottom:1px #eee solid; margin-top:-1px;"></li>';
                unset($cat_html);
            }

            //查询商品关键字
            if ($res_num < 10) {
                $keyword_res = Goods::select('goods_name')
                    ->whereRaw("goods_name like '%$keyword%' OR pinyin_keyword LIKE '%$keyword%'")
                    ->where('is_delete', 0)
                    ->where('is_on_sale', 1)
                    ->where('is_alone_sale', 1);

                if ($this->config['review_goods'] == 1) {
                    $keyword_res = $keyword_res->where('review_status', '>', 2);
                }

                $keyword_res = $this->dscRepository->getAreaLinkGoods($keyword_res, $area_id, $area_city);

                $keyword_res = $keyword_res->groupBy('goods_name');

                $keyword_res = $keyword_res->get();

                $keyword_res = $keyword_res ? $keyword_res->toArray() : [];

                $res_count = count($keyword_res);
                if ($res_count <= 0) {
                    $html .= '</ul>';

                    if ($html == '<ul id="suggestions_list_id"><input type="hidden" value="1" name="selectKeyOne" id="keyOne" /></ul>') {
                        $html = '';
                    }

                    echo $html;
                }
                $len = 10 - $res_num;
                for ($i = 0; $i < $len; $i++) {
                    if ($res_count == $i) {
                        break;
                    }
                    $scws_res = scws($keyword_res[$i]['goods_name']); //这里可以把关键词分词：诺基亚，耳机
                    $arr = explode(',', $scws_res);
                    //@author guan end

                    $arr_keyword = $arr;
                    $goods_ids = [];
                    if ($arr) {
                        $res = Tag::whereRaw(1);
                        foreach ($arr as $key => $val) {
                            $val = !empty($val) ? addslashes($val) : '';
                            $val = mysql_like_quote(trim($val));

                            $res = $res->orWhere('tag_words', 'like', '%' . $val . '%');
                        }

                        $res = $res->groupBy('goods_id')->get();

                        $res = $res ? $res->toArray() : [];

                        $goods_ids = $this->baseRepository->getKeyPluck($res, 'goods_id');
                    }

                    $goods_ids = !empty($goods_ids) ? array_unique($goods_ids) : [];

                    $count = Goods::where('is_delete', 0)->where('is_on_sale', 1)->where('is_alone_sale', 1);

                    if ($this->config['review_goods'] == 1) {
                        $count = $count->where('review_status', '>', 2);
                    }

                    $goods_arr = [
                        'goods_ids' => $goods_ids,
                        'keywords' => $arr_keyword
                    ];
                    $count = $count->where(function ($query) use ($goods_arr) {
                        if ($goods_arr['goods_ids']) {
                            $query = $query->whereIn('goods_id', $goods_arr['goods_ids']);
                        }

                        $query->orWhere(function ($query) use ($goods_arr) {
                            if ($goods_arr['keywords']) {
                                $query->where(function ($query) use ($goods_arr) {
                                    foreach ($goods_arr['keywords'] as $key => $val) {
                                        $query->where(function ($query) use ($val) {
                                            $val = mysql_like_quote(trim($val));

                                            $query = $query->orWhere('goods_name', 'like', '%' . $val . '%');

                                            $query = $query->orWhere('goods_sn', 'like', '%' . $val . '%');

                                            $query->orWhere('keywords', 'like', '%' . $val . '%');
                                        });
                                    }
                                });
                            }
                        });
                    });

                    $count = $count->count();

                    if ($count <= 0) {
                        continue;
                    }

                    if (in_array($keyword_res[$i]['goods_name'], $exist_keyword)) {
                        continue;
                    }

                    $keyword_new_name = $keyword_res[$i]['goods_name'];
                    $this->cut_str($keyword_new_name, 25);

                    $keyword_style = $keyword ? preg_replace("/($keyword)/i", "<font style='font-weight:normal;color:#ec5151;'>$1</font>", $keyword_new_name) : '';
                    $html .= '<li onmouseover="_over(this);" onmouseout="_out(this);" title="' . $keyword_new_name . '" onClick="javascript:fill(\'' . $keyword_new_name . '\');"><div class="left-span">&nbsp;' . $keyword_style . '</div>&nbsp;<b>' . '</b>' . '<div class="suggest_span">约' . $count . '个商品</div></li>';
                }
            }

            $html .= '</ul>';

            if ($html == '<ul id="suggestions_list_id"><input type="hidden" value="1" name="selectKeyOne" id="keyOne" /></ul>') {
                $html = '';
            }

            echo $html;
        }
    }

    /**
     *  截取指定的中英文字符的长度
     *
     *    指定字符串
     *    保留长度
     *    开始位置
     *    编码
     */
    private function cut_str($string, $sublen, $start = 0, $code = 'gbk')
    {
        if ($code == 'utf-8') {
            $pa = "/[\x01-\x7f]|[\xc2-\xdf][\x80-\xbf]|\xe0[\xa0-\xbf][\x80-\xbf]|[\xe1-\xef][\x80-\xbf][\x80-\xbf]|\xf0[\x90-\xbf][\x80-\xbf][\x80-\xbf]|[\xf1-\xf7][\x80-\xbf][\x80-\xbf][\x80-\xbf]/";
            preg_match_all($pa, $string, $t_string);
            if (count($t_string[0]) - $start > $sublen) {
                return join('', array_slice($t_string[0], $start, $sublen)) . "...";
            }
            return join('', array_slice($t_string[0], $start, $sublen));
        } else {
            $start = $start * 2;
            $sublen = $sublen * 2;
            $strlen = strlen($string);
            $tmpstr = '';

            for ($i = 0; $i < $strlen; $i++) {
                if ($i >= $start && $i < ($start + $sublen)) {
                    if (ord(substr($string, $i, 1)) > 129) {
                        $tmpstr .= substr($string, $i, 2);
                    } else {
                        $tmpstr .= substr($string, $i, 1);
                    }
                }

                if (ord(substr($string, $i, 1)) > 129) {
                    $i++;
                }
            }
            //超出多余的字段就显示...

            if (strlen($tmpstr) < $strlen) {
                $tmpstr .= "";
            }

            return $tmpstr;
        }
    }
}
