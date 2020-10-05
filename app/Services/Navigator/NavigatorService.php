<?php

namespace App\Services\Navigator;

use App\Models\Nav;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\DscRepository;
use App\Services\Category\CategoryService;

class NavigatorService
{
    protected $baseRepository;
    protected $dscRepository;
    protected $config;
    protected $categoryService;

    public function __construct(
        BaseRepository $baseRepository,
        DscRepository $dscRepository,
        CategoryService $categoryService
    )
    {
        $this->baseRepository = $baseRepository;
        $this->dscRepository = $dscRepository;
        $this->config = $this->dscRepository->dscConfig();
        $this->categoryService = $categoryService;
    }

    /**
     * 取得自定义导航栏列表
     *
     * @param string $ctype
     * @param array $catlist
     * @return array|bool|\Illuminate\Cache\CacheManager|mixed
     * @throws \Exception
     */
    public function getNavigator($ctype = '', $catlist = [])
    {
        $cur_url = substr(strrchr(request()->server('REQUEST_URI'), '/'), 1);

        if (intval($this->config['rewrite'])) {
            if (strpos($cur_url, '-')) {
                preg_match('/([a-z]*)-([0-9]*)/', $cur_url, $matches);
                $cur_url = $matches[1] . '.php?id=' . $matches[2];
            }
        } else {
            $cur_url = substr(strrchr(request()->server('REQUEST_URI'), '/'), 1);
        }

        $cur_url = empty($cur_url) ? 'index.php' : $cur_url;

        $noindex = false;
        $active = 0;


        $cache_name = 'get_navigator_rewrite_1';
        $navlist = cache($cache_name);
        $navlist = !is_null($navlist) ? $navlist : false;

        if ($navlist === false) {

            $navlist = [
                'top' => [],
                'middle' => [],
                'bottom' => []
            ];

            $res = Nav::where('ifshow', 1)->orderBy('type')->orderBy('vieworder');
            $res = $this->baseRepository->getToArrayGet($res);

            if ($res) {
                foreach ($res as $row) {
                    $navlist[$row['type']][] = [
                        'name' => $row['name'],
                        'opennew' => $row['opennew'],
                        'url' => $this->dscRepository->setRewriteUrl($row['url']),
                        'ctype' => $row['ctype'],
                        'cid' => $row['cid'],
                    ];
                }
            }

            cache()->forever($cache_name, $navlist);
        }

        if ($navlist) {
            /* 遍历自定义是否存在currentPage */
            if (isset($navlist['middle']) && $navlist['middle']) {
                foreach ($navlist['middle'] as $k => $v) {
                    $condition = empty($ctype) ? (strpos($v['url'], $cur_url) !== false) : (strpos($v['url'], $cur_url) !== false && strlen($cur_url) == strlen($v['url']));
                    if ($condition) {
                        $navlist['middle'][$k]['active'] = 1;
                        $noindex = true;
                        $active += 1;
                    }
                    if (substr($v['url'], 0, 8) == 'category') {
                        $cat_id = $v['cid'];
                        $cat_list = $this->categoryService->getCategoriesTreeXaphp($cat_id);
                        $navlist['middle'][$k]['cat'] = 1;
                        $navlist['middle'][$k]['cat_list'] = $cat_list;
                    }
                }
            }

            if ($catlist && !empty($ctype) && $active < 1) {
                foreach ($catlist as $key => $val) {
                    foreach ($navlist['middle'] as $k => $v) {
                        if (!empty($v['ctype']) && $v['ctype'] == $ctype && $v['cid'] == $val && $active < 1) {
                            $navlist['middle'][$k]['active'] = 1;
                            $noindex = true;
                            $active += 1;
                        }
                    }
                }
            }

            if ($noindex == false) {
                $navlist['config']['index'] = 1;
            }
        }

        return $navlist;
    }
}