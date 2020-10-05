<?php

namespace App\Services\Goods;


use App\Models\Comment;
use App\Repositories\Common\BaseRepository;

class GoodsCommentService
{
    protected $baseRepository;

    public function __construct(
        BaseRepository $baseRepository
    )
    {
        $this->baseRepository = $baseRepository;
    }

    /**
     * 获取评价买家对商品印象词的个数
     *
     * @param int $goods_id
     * @param string $txt
     * @return int
     */
    public function commentGoodsTagNum($goods_id = 0, $txt = '')
    {
        $txt = !empty($txt) ? trim($txt) : '';

        $res = Comment::where('id_value', $goods_id);
        $res = $this->baseRepository->getToArrayGet($res);
        $str = $this->baseRepository->getKeyPluck($res, 'goods_tag');
        $str = $this->baseRepository->getImplode($str);

        if ($str && $txt) {
            $str = substr($str, 0, -1);
            $num = substr_count($str, $txt);
        } else {
            $num = 0;
        }

        return $num;
    }
}