<?php

namespace App\Custom\Guestbook\Services;

/**
 * Class GuestbookService
 * @package App\Custom\Guestbook\Services
 */
class GuestbookService
{

    /**
     * test data
     * @return array
     */
    public function content()
    {
        $list = [];

        for ($i = 1; $i <= 10; $i++) {
            $list[$i] = [
                'id' => $i,
                'username' => 'username ' . $i,
                'content' => 'guest content_' . $i,
            ];
        }

        return $list;
    }
}
