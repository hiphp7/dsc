<?php

namespace App\Plugins\Cron\Manage;

use App\Models\Article;
use App\Models\AutoManage;
use App\Models\Goods;

$cron_lang = __DIR__ . '/Languages/' . config('shop.lang') . '.php';

if (file_exists($cron_lang)) {
    require_once($cron_lang);
}

$debug = config('app.debug'); // true 开启日志 false 关闭日志

$time = $this->timeRepository->getGmTime();

$limit = isset($cron['auto_manage_count']) && !empty($cron['auto_manage_count']) ? $cron['auto_manage_count'] : 5;

$model = AutoManage::where(function ($query) use ($time) {
    $query->where('starttime', '>', 0)->where('starttime', '<=', $time);
})->orWhere(function ($query) use ($time) {
    $query->where('endtime', '>', 0)->where('endtime', '<=', $time);
});

$autodb = $model->limit($limit)->get();

$autodb = $autodb ? $autodb->toArray() : [];

if (!empty($autodb)) {
    foreach ($autodb as $key => $val) {
        $del = $up = false;
        if ($val['type'] == 'goods') {
            $goods = true;
        } else {
            $goods = false;
        }

        //上下架判断
        if (!empty($val['starttime']) && !empty($val['endtime'])) {
            //上下架时间均设置
            if ($val['starttime'] <= $time && $time < $val['endtime']) {
                //上架时间 <= 当前时间 < 下架时间
                $up = true;
                $del = false;
            } elseif ($val['starttime'] >= $time && $time > $val['endtime']) {
                //下架时间 <= 当前时间 < 上架时间
                $up = false;
                $del = false;
            } elseif ($val['starttime'] == $time && $time == $val['endtime']) {
                //下架时间 == 当前时间 == 上架时间
                deleteAutoManage($val['item_id'], $val['type']);
                continue;
            } elseif ($val['starttime'] > $val['endtime']) {
                // 下架时间 < 上架时间 < 当前时间
                $up = true;
                $del = true;
            } elseif ($val['starttime'] < $val['endtime']) {
                // 上架时间 < 下架时间 < 当前时间
                $up = false;
                $del = true;
            } else {
                // 上架时间 = 下架时间 < 当前时间
                deleteAutoManage($val['item_id'], $val['type']);
                continue;
            }
        } elseif (!empty($val['starttime'])) {
            //只设置了上架时间
            $up = true;
            $del = true;
        } else {
            //只设置了下架时间
            $up = false;
            $del = true;
        }

        if ($goods) {
            if ($up) {
                updateGoods($val['item_id'], ['is_on_sale' => 1]);
            } else {
                updateGoods($val['item_id'], ['is_on_sale' => 0]);
            }
        } else {
            if ($up) {
                updateArticle($val['item_id'], ['is_open' => 1]);
            } else {
                updateArticle($val['item_id'], ['is_open' => 0]);
            }
        }

        if ($del) {
            deleteAutoManage($val['item_id'], $val['type']);
        } else {
            if ($up) {
                updateAutoManage($val['item_id'], $val['type'], ['starttime' => 0]);
            } else {
                updateAutoManage($val['item_id'], $val['type'], ['endtime' => 0]);
            }
        }
    }
}

if ($debug == true && $autodb) {
    logResult('==================== cron manage log ====================', [], 'info', 'single');
    logResult($autodb, [], 'info', 'single');
}

/**
 * 更新商品
 * @param int $goods_id
 * @param array $data
 * @return bool
 */
function updateGoods($goods_id = 0, $data = [])
{
    if (empty($goods_id) || empty($data)) {
        return false;
    }

    return Goods::where('goods_id', $goods_id)->update($data);
}

/**
 * 更新文章
 * @param int $article_id
 * @param array $data
 * @return bool
 */
function updateArticle($article_id = 0, $data = [])
{
    if (empty($article_id) || empty($data)) {
        return false;
    }

    return Article::where('article_id', $article_id)->update($data);
}

/**
 * 更新
 * @param int $item_id
 * @param string $type
 * @param array $data
 * @return bool
 */
function updateAutoManage($item_id = 0, $type = '', $data = [])
{
    if (empty($item_id) || empty($type) || empty($data)) {
        return false;
    }

    return AutoManage::where('item_id', $item_id)->where('type', $type)->update($data);
}

/**
 * 删除
 * @param int $item_id
 * @param string $type
 * @return bool
 */
function deleteAutoManage($item_id = 0, $type = '')
{
    if (empty($item_id) || empty($type)) {
        return false;
    }

    return AutoManage::where('item_id', $item_id)->where('type', $type)->delete();
}