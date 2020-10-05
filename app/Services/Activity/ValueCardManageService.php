<?php

namespace App\Services\Activity;

use App\Models\ValueCardType;

class ValueCardManageService
{
    /**
     * 添加储值卡
     *
     * @param $value_card
     * @param $time
     * @return mixed
     */
    public function ValueCardTypeInsert($value_card, $time)
    {
        $value_card['add_time'] = $time;
        $id = ValueCardType::insertGetId($value_card);

        return $id;
    }
}