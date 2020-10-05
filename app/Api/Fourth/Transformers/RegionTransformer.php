<?php

namespace App\Api\Fourth\Transformers;

use App\Api\Foundation\Transformer\Transformer;

/**
 * Class RegionTransformer
 * @package App\Api\Fourth\Transformer
 */
class RegionTransformer extends Transformer
{

    /**
     * @param $item
     * @return array|mixed
     */
    public function transform($item)
    {
        return [
            'id' => $item['region_id'],
            'name' => $item['region_name'],
        ];
    }
}
