<?php

namespace App\Api\Foundation\Transformer;

/**
 * Class Transformer
 * @package App\Api\Foundation\Transformer
 */
abstract class Transformer
{
    /**
     * @param $data
     * @return array
     */
    public function transformCollection($data)
    {
        return array_map([$this, 'transform'], $data);
    }

    /**
     * @param $item
     * @return mixed
     */
    abstract public function transform($item);
}
