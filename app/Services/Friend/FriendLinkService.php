<?php

namespace App\Services\Friend;


use App\Models\FriendLink;
use App\Models\PartnerList;
use App\Repositories\Common\DscRepository;

class FriendLinkService
{
    protected $dscRepository;

    public function __construct(
        DscRepository $dscRepository
    )
    {
        $this->dscRepository = $dscRepository;
    }

    /**
     * 获得所有的友情链接
     *
     * @access  private
     * @return  array
     */
    public function getIndexGetLinks($table = 'friend_link')
    {
        if ($table == 'partner_list') {
            $res = PartnerList::orderBy('show_order')->get();
        } else {
            $res = FriendLink::orderBy('show_order')->get();
        }

        $res = $res ? $res->toArray() : [];

        $links['img'] = $links['txt'] = [];

        if ($res) {
            foreach ($res as $row) {
                if ($row['link_logo']) {
                    $row['link_logo'] = $this->dscRepository->getImagePath($row['link_logo']);
                } else {
                    $row['link_logo'] = '';
                }

                if (!empty($row['link_logo'])) {
                    $links['img'][] = ['name' => $row['link_name'],
                        'url' => $row['link_url'],
                        'logo' => $row['link_logo']];
                } else {
                    $links['txt'][] = ['name' => $row['link_name'],
                        'url' => $row['link_url']];
                }
            }
        }

        return $links;
    }
}