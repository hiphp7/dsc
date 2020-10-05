<?php

namespace App\Services\Wechat;

use App\Models\WechatMedia;
use App\Models\WechatReply;
use App\Repositories\Common\DscRepository;
use App\Repositories\Common\TimeRepository;

/**
 * 微信素材
 * Class WechatMediaService
 * @package App\Services\Wechat
 */
class WechatMediaService
{
    protected $wechatService;
    protected $config;
    protected $timeRepository;
    protected $dscRepository;

    public function __construct(
        WechatService $wechatService,
        TimeRepository $timeRepository,
        DscRepository $dscRepository
    )
    {
        $this->wechatService = $wechatService;
        $this->timeRepository = $timeRepository;
        $this->dscRepository = $dscRepository;

        /* 商城配置信息 */
        $this->config = $this->dscRepository->dscConfig();
    }

    /**
     * 微信素材详情 （文章页面）
     *
     * @param int $id
     * @return array
     * @throws \Exception
     */
    public function WechatMediaDetail($id = 0)
    {
        // 单图文
        $res = WechatMedia::where('id', $id)->where('article_id', '')->first();
        $res = $res ? $res->toArray() : [];

        if (empty($res)) {
            return [];
        }

        $config = $this->wechatService->getWechatConfigById($res['wechat_id']);

        $res['author'] = !empty($res['author']) ? $res['author'] : ($config['name'] ?? '');
        $res['add_time'] = $this->timeRepository->getLocalDate($this->config['time_format'], $res['add_time']);
        // 摘要
        if (isset($res['digest']) && $res['digest']) {
            $res['digest'] = strip_tags($res['digest']);
        }
        // 内容
        if (isset($res['content']) && $res['content']) {
            // 过滤样式 手机自适应
            $res['content'] = $this->dscRepository->contentStyleReplace($res['content']);
            // 显示文章详情图片 （本地或OSS）
            $res['content'] = $this->dscRepository->getContentImgReplace($res['content']);

            $res['content'] = html_out($res['content']);
        }
        // 封面图
        $res['file'] = $this->dscRepository->getImagePath($res['file']);

        return $res;
    }

    /**
     * 微信自动回复
     *
     * @param int $wechat_id
     * @param string $type
     * @return array
     */
    public function wechatReply($wechat_id = 0, $type = '')
    {
        if (empty($type)) {
            return [];
        }

        $replyInfo = WechatReply::where('wechat_id', $wechat_id)->where('type', $type)
            ->first();
        return $replyInfo ? $replyInfo->toArray() : [];
    }

    /**
     * 更新微信自动回复
     * @param int $wechat_id
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function updateWechatReply($wechat_id = 0, $id = 0, $data = [])
    {
        if (empty($data)) {
            return false;
        }

        return WechatReply::where('id', $id)->where('wechat_id', $wechat_id)->update($data);
    }

    /**
     * 添加微信自动回复
     * @param array $data
     * @return bool
     */
    public function createWechatReply($data = [])
    {
        if (empty($data)) {
            return false;
        }

        return WechatReply::create($data);
    }

    /**
     * 关键词自动回复
     * @param int $wechat_id
     * @param string $keywords
     * @return array
     */
    public function wechatReplyKeywords($wechat_id = 0, $keywords = '')
    {
        $model = WechatReply::where('wechat_id', $wechat_id)->where('type', 'keywords');

        $model = $model->whereHas('wechatRuleKeywords', function ($query) use ($keywords) {
            $query->where('rule_keywords', $keywords);
        });

        $result = $model->orderBy('add_time', 'DESC')
            ->first();

        return $result ? $result->toArray() : [];
    }

    /**
     * 微信关键词自动回复列表
     * @param int $wechat_id
     * @param array $offset
     * @return array
     */
    public function wechatReplyKeywordsList($wechat_id = 0, $offset = [])
    {
        $model = WechatReply::where('wechat_id', $wechat_id)
            ->where('type', 'keywords');

        $model = $model->with([
            'wechatRuleKeywordsList' => function ($query) use ($wechat_id) {
                $query->where('wechat_id', $wechat_id)->orderBy('id', 'DESC');
            }
        ]);

        $list = $model->orderBy('add_time', 'DESC')
            ->get();

        return $list ? $list->toArray() : [];
    }

    /**
     * 微信素材信息
     * @param int $media_id
     * @return array
     */
    public function wechatMediaInfo($media_id = 0)
    {
        $media = WechatMedia::where('id', $media_id)->first();

        return $media ? $media->toArray() : [];
    }

    /**
     * 微信多图文素材信息
     * @param int $wechat_id
     * @param array $article_ids
     * @return array
     */
    public function wechatMediaInfoByArticle($wechat_id = 0, $article_ids = [])
    {
        if (empty($article_ids)) {
            return [];
        }

        $article = WechatMedia::whereIn('article_id', $article_ids)
            ->where('wechat_id', $wechat_id)
            ->orderBy('sort', 'ASC')
            ->orderBy('add_time', 'DESC')
            ->get();

        return $article ? $article->toArray() : [];
    }

    /**
     * 微信素材列表
     * @param int $wechat_id
     * @param array $offset
     * @param string $type
     * @param string $sort
     * @param string $order
     * @return array
     */
    public function wechatMediaList($wechat_id = 0, $offset = [], $type = '', $sort = 'sort', $order = 'ASC')
    {
        $model = WechatMedia::where('wechat_id', $wechat_id);

        if (!empty($type)) {
            $model = $model->where('type', $type);

            if ($type != 'news') {
                $model = $model->where('file', '<>', '');
            }
        }

        $total = $model->count();

        if (!empty($offset)) {
            $model = $model->offset($offset['start'])
                ->limit($offset['limit']);
        }

        $list = $model->orderBy($sort, $order)
            ->orderBy('add_time', 'DESC')
            ->get();

        $list = $list ? $list->toArray() : [];

        return ['list' => $list, 'total' => $total];
    }

    /**
     * 微信单图文素材列表
     * @param int $wechat_id
     * @param array $offset
     * @param string $type
     * @return array
     */
    public function wechatMediaArticle($wechat_id = 0, $offset = [], $type = '')
    {
        $model = WechatMedia::where('wechat_id', $wechat_id)->where('article_id', '');

        if (!empty($type)) {
            $model = $model->where('type', $type);
        }

        $total = $model->count();

        if (!empty($offset)) {
            $model = $model->offset($offset['start'])
                ->limit($offset['limit']);
        }

        $list = $model->orderBy('sort', 'ASC')
            ->orderBy('add_time', 'DESC')
            ->get();

        $list = $list ? $list->toArray() : [];

        return ['list' => $list, 'total' => $total];
    }

    /**
     * 微信多图文素材列表
     * @param int $wechat_id
     * @param array $offset
     * @param string $type
     * @return array
     */
    public function wechatMediaArticleList($wechat_id = 0, $offset = [], $type = '')
    {
        $model = WechatMedia::where('wechat_id', $wechat_id)->where('article_id', '<>', '');

        if (!empty($type)) {
            $model = $model->where('type', $type);
        }

        $total = $model->count();

        if (!empty($offset)) {
            $model = $model->offset($offset['start'])
                ->limit($offset['limit']);
        }

        $list = $model->orderBy('sort', 'ASC')
            ->orderBy('add_time', 'DESC')
            ->get();

        $list = $list ? $list->toArray() : [];

        return ['list' => $list, 'total' => $total];
    }

    /**
     * 更新微信素材
     * @param int $wechat_id
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function updateWechatMedia($wechat_id = 0, $id = 0, $data = [])
    {
        if (empty($data)) {
            return false;
        }

        return WechatMedia::where('id', $id)->where('wechat_id', $wechat_id)->update($data);
    }

    /**
     * 添加微信素材
     * @param array $data
     * @return bool
     */
    public function createWechatMedia($data = [])
    {
        if (empty($data)) {
            return false;
        }

        return WechatMedia::create($data);
    }

    /**
     * 删除微信素材
     * @param int $wechat_id
     * @param int $id
     * @return array
     */
    public function deleteWechatMedia($wechat_id = 0, $id = 0)
    {
        if (empty($id)) {
            return [];
        }

        $model = WechatMedia::where('id', $id)->where('wechat_id', $wechat_id)->first();

        $pic = $model->file;
        $thumb = $model->thumb;

        $model->delete();

        return ['pic' => $pic, 'thumb' => $thumb];
    }


}
