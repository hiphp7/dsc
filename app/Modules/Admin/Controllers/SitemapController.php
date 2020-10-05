<?php

namespace App\Modules\Admin\Controllers;

use App\Libraries\GoogleSitemap;
use App\Libraries\GoogleSitemapItem;
use App\Repositories\Common\DscRepository;

/**
 * 站点地图生成程序
 */
class SitemapController extends InitController
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
        /* 检查权限 */
        admin_priv('sitemap');

        if (request()->server('REQUEST_METHOD') == 'GET') {
            /*------------------------------------------------------ */
            //-- 设置更新频率
            /*------------------------------------------------------ */

            $config = unserialize($GLOBALS['_CFG']['sitemap']);
            $this->smarty->assign('config', $config);
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['sitemap']);
            $this->smarty->assign('arr_changefreq', [1, 0.9, 0.8, 0.7, 0.6, 0.5, 0.4, 0.3, 0.2, 0.1]);
            return $this->smarty->display('sitemap.dwt');
        } else {
            /*------------------------------------------------------ */
            //-- 生成站点地图
            /*------------------------------------------------------ */


            $domain = $this->dsc->url();
            $today = local_date('Y-m-d');

            $sm = new GoogleSitemap();
            $smi = new GoogleSitemapItem($domain, $today, $_POST['homepage_changefreq'], $_POST['homepage_priority']);

            $sm->add_item($smi);

            $config = [
                'homepage_changefreq' => $_POST['homepage_changefreq'],
                'homepage_priority' => $_POST['homepage_priority'],
                'category_changefreq' => $_POST['category_changefreq'],
                'category_priority' => $_POST['category_priority'],
                'content_changefreq' => $_POST['content_changefreq'],
                'content_priority' => $_POST['content_priority'],
            ];
            $config = serialize($config);

            $this->db->query("UPDATE " . $this->dsc->table('shop_config') . " SET VALUE='$config' WHERE code='sitemap'");

            /* 商品分类 */
            $sql = "SELECT cat_id,cat_name FROM " . $this->dsc->table('category') . " ORDER BY parent_id";
            $res = $this->db->query($sql);

            foreach ($res as $row) {
                if (strpos($this->dscRepository->buildUri('category', ['cid' => $row['cat_id']], $row['cat_name']), $domain) === false) {
                    $build_uri = $domain . $this->dscRepository->buildUri('category', ['cid' => $row['cat_id']], $row['cat_name']);
                } else {
                    $build_uri = $this->dscRepository->buildUri('category', ['cid' => $row['cat_id']], $row['cat_name']);
                }

                //echo $build_uri . "<br/>";

                $smi = new GoogleSitemapItem(
                    $build_uri,
                    $today,
                    $_POST['category_changefreq'],
                    $_POST['category_priority']
                );
                $sm->add_item($smi);
            }

            /* 文章分类 */
            $sql = "SELECT cat_id,cat_name FROM " . $this->dsc->table('article_cat') . " WHERE cat_type=1";
            $res = $this->db->query($sql);

            foreach ($res as $row) {
                if (strpos($this->dscRepository->buildUri('article_cat', ['acid' => $row['cat_id']], $row['cat_name']), $domain) === false) {
                    $build_uri = $domain . $this->dscRepository->buildUri('article_cat', ['acid' => $row['cat_id']], $row['cat_name']);
                } else {
                    $build_uri = $this->dscRepository->buildUri('article_cat', ['acid' => $row['cat_id']], $row['cat_name']);
                }

                $smi = new GoogleSitemapItem(
                    $build_uri,
                    $today,
                    $_POST['category_changefreq'],
                    $_POST['category_priority']
                );
                $sm->add_item($smi);
            }

            /* 商品 */
            $sql = "SELECT goods_id, goods_name FROM " . $this->dsc->table('goods') . " WHERE is_delete = 0";
            $res = $this->db->query($sql);

            foreach ($res as $row) {
                if (strpos($this->dscRepository->buildUri('goods', ['gid' => $row['goods_id']], $row['goods_name']), $domain) === false) {
                    $build_uri = $domain . $this->dscRepository->buildUri('goods', ['gid' => $row['goods_id']], $row['goods_name']);
                } else {
                    $build_uri = $this->dscRepository->buildUri('goods', ['gid' => $row['goods_id']], $row['goods_name']);
                }

                $smi = new GoogleSitemapItem(
                    $build_uri,
                    $today,
                    $_POST['content_changefreq'],
                    $_POST['content_priority']
                );
                $sm->add_item($smi);
            }

            /* 文章 */
            $sql = "SELECT article_id,title,file_url,open_type FROM " . $this->dsc->table('article') . " WHERE is_open=1";
            $res = $this->db->query($sql);

            foreach ($res as $row) {
                $article_url = $row['open_type'] != 1 ? $this->dscRepository->buildUri('article', ['aid' => $row['article_id']], $row['title']) : trim($row['file_url']);

                if (strpos($article_url, $domain) === false) {
                    $build_uri = $domain . $article_url;
                } else {
                    $build_uri = $article_url;
                }

                $smi = new GoogleSitemapItem(
                    $build_uri,
                    $today,
                    $_POST['content_changefreq'],
                    $_POST['content_priority']
                );
                $sm->add_item($smi);
            }

            clear_cache_files();    // 清除缓存

            $sm_file = public_path('sitemaps.xml');
            if ($sm->build($sm_file)) {
                return sys_msg(sprintf($GLOBALS['_LANG']['generate_success'], $this->dsc->url() . "sitemaps.xml"));
            } else {
                $sm_file = storage_public(DATA_DIR . '/sitemaps.xml');
                if ($sm->build($sm_file)) {
                    return sys_msg(sprintf($GLOBALS['_LANG']['generate_success'], $this->dsc->url() . DATA_DIR . '/sitemaps.xml'));
                } else {
                    return sys_msg(sprintf($GLOBALS['_LANG']['generate_failed']));
                }
            }
        }
    }
}
