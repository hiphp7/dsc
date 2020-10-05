<?php

namespace App\Http\Controllers;

use App\Models\CollectStore;
use App\Repositories\Common\DscRepository;

/**
 * 用户店铺关注
 */
class CollectionStoreController extends InitController
{
    protected $dscRepository;

    public function __construct(
        DscRepository $dscRepository
    )
    {
        $this->dscRepository = $dscRepository;
    }

    public function index()
    {
        /**
         * Start
         *
         * @param $warehouse_id 仓库ID
         * @param $area_id 省份ID
         * @param $area_city 城市ID
         */
        $warehouse_id = $this->warehouseId();
        $area_id = $this->areaId();
        $area_city = $this->areaCity();
        /* End */

        load_helper('clips');

        $this->dscRepository->helpersLang('user');

        $user_id = session('user_id', 0);

        $result = ['error' => 0, 'message' => '', 'content' => ''];

        $page = intval(request()->input('page', 1));

        if ($result['error'] == 0) {
            $record_count = CollectStore::where('user_id', $user_id)->count();

            $size = 5;
            $collection_store = get_collection_store($user_id, $record_count, $page, 'collection_store_gotoPage', $size, $warehouse_id, $area_id, $area_city);

            $this->smarty->assign('store_list', $collection_store['store_list']);
            $this->smarty->assign('pager', $collection_store['pager']);
            $this->smarty->assign('count', $collection_store['record_count']);
            $this->smarty->assign('size', $collection_store['size']);

            $this->smarty->assign('url', url('/') . '/');
            $lang_list = [
                'UTF8' => $GLOBALS['_LANG']['charset']['utf8'],
                'GB2312' => $GLOBALS['_LANG']['charset']['zh_cn'],
                'BIG5' => $GLOBALS['_LANG']['charset']['zh_tw'],
            ];
            $this->smarty->assign('lang_list', $lang_list);
            $this->smarty->assign('user_id', $user_id);

            $this->smarty->assign('lang', $GLOBALS['_LANG']);

            $result['content'] = $this->smarty->fetch("library/collection_store_list.lbi");
            $result['pages'] = $this->smarty->fetch("library/pages_ajax.lbi");
        }

        return response()->json($result);
    }
}
