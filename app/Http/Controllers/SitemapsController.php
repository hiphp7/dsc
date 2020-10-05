<?php

namespace App\Http\Controllers;

use App\Libraries\Sitemap;
use App\Models\Article;
use App\Models\ArticleCat;
use App\Models\Category;
use App\Models\Goods;
use App\Repositories\Common\DscRepository;

/**
 * google sitemap 文件
 */
class SitemapsController extends InitController
{
    protected $dscRepository;

    public function __construct(
        DscRepository $dscRepository
    )
    {
        $this->dscRepository = $dscRepository;
    }

    public function index()
    {
        if (file_exists(storage_public(DATA_DIR . '/sitemap.dat')) && time() - filemtime(storage_public(DATA_DIR . '/sitemap.dat')) < 86400) {
            $out = file_get_contents(storage_public(DATA_DIR . '/sitemap.dat'));
        } else {
            $site_url = url('/');
            $sitemap = new Sitemap();
            $config = unserialize($GLOBALS['_CFG']['sitemap']);
            $item = [
                'loc' => "$site_url/",
                'lastmod' => local_date('Y-m-d'),
                'changefreq' => $config['homepage_changefreq'],
                'priority' => $config['homepage_priority'],
            ];
            $sitemap->item($item);

            /* 商品分类 */
            $res = Category::whereRaw(1)->orderBy('parent_id')->get();
            $res = $res ? $res->toArray() : [];

            if ($res) {
                foreach ($res as $row) {
                    if (strpos($this->dscRepository->buildUri('category', ['cid' => $row['cat_id']], $row['cat_name']), $site_url) === false) {
                        $cat_loc = "$site_url/" . $this->dscRepository->buildUri('category', ['cid' => $row['cat_id']], $row['cat_name']);
                    } else {
                        $cat_loc = $this->dscRepository->buildUri('category', ['cid' => $row['cat_id']], $row['cat_name']);
                    }

                    $item = [
                        'loc' => $cat_loc,
                        'lastmod' => local_date('Y-m-d'),
                        'changefreq' => $config['category_changefreq'],
                        'priority' => $config['category_priority'],
                    ];
                    $sitemap->item($item);
                }
            }

            /* 文章分类 */
            $res = ArticleCat::where('cat_type', 1)->get();
            $res = $res ? $res->toArray() : [];

            if ($res) {
                foreach ($res as $row) {
                    if (strpos($this->dscRepository->buildUri('article_cat', ['acid' => $row['cat_id']], $row['cat_name']), $site_url) === false) {
                        $art_cat_loc = "$site_url/" . $this->dscRepository->buildUri('article_cat', ['acid' => $row['cat_id']], $row['cat_name']);
                    } else {
                        $art_cat_loc = $this->dscRepository->buildUri('article_cat', ['acid' => $row['cat_id']], $row['cat_name']);
                    }

                    $item = [
                        'loc' => $art_cat_loc,
                        'lastmod' => local_date('Y-m-d'),
                        'changefreq' => $config['category_changefreq'],
                        'priority' => $config['category_priority'],
                    ];
                    $sitemap->item($item);
                }
            }

            /* 商品 */
            $res = Goods::where('is_delete', 0)->take(300)->get();
            $res = $res ? $res->toArray() : [];

            if ($res) {
                foreach ($res as $row) {
                    if (strpos($this->dscRepository->buildUri('goods', ['gid' => $row['goods_id']], $row['goods_name']), $site_url) === false) {
                        $goods_loc = "$site_url/" . $this->dscRepository->buildUri('goods', ['gid' => $row['goods_id']], $row['goods_name']);
                    } else {
                        $goods_loc = $this->dscRepository->buildUri('goods', ['gid' => $row['goods_id']], $row['goods_name']);
                    }

                    $item = [
                        'loc' => $goods_loc,
                        'lastmod' => local_date('Y-m-d', $row['last_update']),
                        'changefreq' => $config['content_changefreq'],
                        'priority' => $config['content_priority'],
                    ];
                    $sitemap->item($item);
                }
            }

            /* 文章 */
            $res = Article::where('is_open', 1)->get();
            $res = $res ? $res->toArray() : [];

            if ($res) {
                foreach ($res as $row) {
                    $article_url = $row['open_type'] != 1 ? $this->dscRepository->buildUri('article', ['aid' => $row['article_id']], $row['title']) : trim($row['file_url']);

                    if (strpos($article_url, $site_url) === false) {
                        $art_loc = "$site_url/" . $article_url;
                    } else {
                        $art_loc = $article_url;
                    }

                    $item = [
                        'loc' => $art_loc,
                        'lastmod' => local_date('Y-m-d', $row['add_time']),
                        'changefreq' => $config['content_changefreq'],
                        'priority' => $config['content_priority'],
                    ];
                    $sitemap->item($item);
                }
            }

            $out = $sitemap->generate();
            file_put_contents(storage_public(DATA_DIR . '/sitemap.dat'), $out);
        }
        if (function_exists('gzencode')) {
            header('Content-type: application/x-gzip');
            $out = gzencode($out, 9);
        } else {
            header('Content-type: application/xml; charset=utf-8');
        }
        return $out;
    }
}
