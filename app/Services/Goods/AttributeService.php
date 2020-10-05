<?php

namespace App\Services\Goods;

use App\Models\Attribute;
use App\Repositories\Common\BaseRepository;

class AttributeService
{
    protected $baseRepository;

    /**
     * AttributeService constructor.
     * @param BaseRepository $baseRepository
     */
    public function __construct(
        BaseRepository $baseRepository
    )
    {
        $this->baseRepository = $baseRepository;
    }

    /**
     * 获取属性信息
     *
     * @param int $attr_id
     */
    public function getAttributeInfo($attr_id = 0)
    {
        $row = Attribute::where('attr_id', $attr_id);
        $row = $this->baseRepository->getToArrayFirst($row);

        return $row;
    }
}
