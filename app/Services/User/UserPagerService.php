<?php

namespace App\Services\User;

class UserPagerService
{
    protected $config;

    public function __construct()
    {
        /* 商城配置信息 */
        $shopConfig = cache('shop_config');
        if (is_null($shopConfig)) {
            $this->config = app(\App\Services\Common\ConfigService::class)->getConfig();
        } else {
            $this->config = $shopConfig;
        }
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
    public function getPager($url, $param, $record_count, $page = 1, $size = 10)
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
        $pager['styleid'] = isset($this->config['page_style']) ? intval($this->config['page_style']) : 0;

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
}
