<?php

namespace App\Services\Merchant;

use App\Libraries\Image;
use App\Models\Category;
use App\Models\MerchantsShopBrand;
use App\Models\MerchantsShopInformation;
use App\Models\MerchantsStepsFields;
use App\Models\MerchantsStepsFieldsCentent;
use App\Models\MerchantsStepsProcess;
use App\Models\MerchantsStepsTitle;
use App\Models\SellerApplyInfo;
use App\Models\SellerGrade;
use App\Models\SellerShopinfo;
use App\Models\Users;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\DscRepository;
use App\Repositories\Common\TimeRepository;
use App\Services\Commission\CommissionService;
use App\Services\Order\OrderService;


class MerchantsUsersListManageService
{
    protected $baseRepository;
    protected $merchantCommonService;
    protected $orderService;
    protected $commissionService;
    protected $dscRepository;
    protected $timeRepository;

    public function __construct(
        BaseRepository $baseRepository,
        OrderService $orderService,
        MerchantCommonService $merchantCommonService,
        CommissionService $commissionService,
        DscRepository $dscRepository,
        TimeRepository $timeRepository
    )
    {
        $this->baseRepository = $baseRepository;
        $this->merchantCommonService = $merchantCommonService;
        $this->orderService = $orderService;
        $this->commissionService = $commissionService;
        $this->dscRepository = $dscRepository;
        $this->timeRepository = $timeRepository;
    }

    /**
     * 返回申请流程列表数据
     *
     * @return array
     * @throws \Exception
     */
    public function stepsUsersList()
    {
        $adminru = get_admin_ru_id();

        /* 过滤条件 */
        $filter['keywords'] = !isset($_REQUEST['keywords']) ? '' : trim($_REQUEST['keywords']);
        if (isset($_REQUEST['is_ajax']) && $_REQUEST['is_ajax'] == 1) {
            $filter['keywords'] = json_str_iconv($filter['keywords']);
        }

        $filter['sort_by'] = empty($_REQUEST['sort_by']) ? 'shop_id' : trim($_REQUEST['sort_by']);
        $filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'DESC' : trim($_REQUEST['sort_order']);
        $filter['user_name'] = empty($_REQUEST['user_name']) ? '' : trim($_REQUEST['user_name']);
        $filter['check'] = isset($_REQUEST['check']) ? intval($_REQUEST['check']) : 0;
        $filter['shopinfo_check'] = isset($_REQUEST['shopinfo_check']) ? intval($_REQUEST['shopinfo_check']) : 0;

        $res = MerchantsShopInformation::whereRaw(1);

        //管理员查询的权限 -- 店铺查询 start
        $filter['store_search'] = empty($_REQUEST['store_search']) ? 0 : intval($_REQUEST['store_search']);
        $filter['merchant_id'] = isset($_REQUEST['merchant_id']) ? intval($_REQUEST['merchant_id']) : 0;
        $filter['store_keyword'] = isset($_REQUEST['store_keyword']) ? trim($_REQUEST['store_keyword']) : '';

        if ($filter['store_search'] != 0) {
            if ($adminru['ru_id'] == 0) {
                $store_type = isset($_REQUEST['store_type']) && !empty($_REQUEST['store_type']) ? intval($_REQUEST['store_type']) : 0;

                if ($filter['store_search'] == 1) {
                    $res = $res->where('user_id', $filter['merchant_id']);
                } elseif ($filter['store_search'] == 2) {
                    $res = $res->where('rz_shopName', 'LIKE', '%' . mysql_like_quote($filter['store_keyword']) . '%');
                }

                if ($filter['store_search'] > 1) {
                    $res = $res->where('user_id', '>0');
                    if ($filter['store_search'] == 3) {
                        $res = $res->where('shoprz_brandName', 'LIKE', '%' . mysql_like_quote($filter['store_keyword']) . '%');
                        if ($store_type) {
                            $res = $res->where('shopNameSuffix', $_REQUEST['store_type']);
                        }
                    }
                }
            }
        }
        //管理员查询的权限 -- 店铺查询 end

        if ($filter['check'] == 1) {
            $res = $res->where('merchants_audit', 0);
        } elseif ($filter['check'] == 2) {
            $res = $res->where('merchants_audit', 1);
        } elseif ($filter['check'] == 3) {
            $res = $res->where('merchants_audit', 0);
        }

        $res = $res->whereHas('getUsers', function ($query) use ($filter) {
            if (!empty($filter['user_name'])) {
                $query->where(function ($query) use ($filter) {
                    $query->where('user_name', 'LIKE', '%' . mysql_like_quote($filter['user_name']) . '%');
                });
            }
        });

        if ($filter['shopinfo_check'] > 0) {
            $res = $res->whereHas('getSellerShopinfo', function ($query) use ($filter) {
                if ($filter['shopinfo_check'] == 1) {
                    $query->where('review_status', 1);
                } elseif ($filter['shopinfo_check'] == 2) {
                    $query->where('review_status', 2);
                } elseif ($filter['shopinfo_check'] == 3) {
                    $query->where('review_status', 3);
                }
            });
        }

        $filter['record_count'] = $res->count();

        /* 分页大小 */
        $filter = page_and_size($filter);

        $res = $res->orderBy($filter['sort_by'], $filter['sort_order'])
            ->offset($filter['start'])
            ->limit($filter['page_size']);
        $users_list = $this->baseRepository->getToArrayGet($res);

        $count = count($users_list);
        for ($i = 0; $i < $count; $i++) {
            $users_list[$i]['user_name'] = Users::where('user_id', $users_list[$i]['user_id'])->value('user_name');
            $users_list[$i]['user_name'] = $users_list[$i]['user_name'] ? $users_list[$i]['user_name'] : '';

            if (isset($GLOBALS['_CFG']['show_mobile']) && $GLOBALS['_CFG']['show_mobile'] == 0 && (strlen($users_list[$i]['user_name']) == 11 && is_numeric($users_list[$i]['user_name']))) {
                $users_list[$i]['user_name'] = $this->dscRepository->stringToStar($users_list[$i]['user_name']);
            }

            $users_list[$i]['cat_name'] = Category::where('cat_id', $users_list[$i]['shop_categoryMain'])->value('cat_name');
            $users_list[$i]['cat_name'] = $users_list[$i]['cat_name'] ? $users_list[$i]['cat_name'] : '';

            $shop_name = $this->merchantCommonService->getShopName($users_list[$i]['user_id'], 1);
            $users_list[$i]['rz_shopName'] = !empty($shop_name) ? $shop_name : '';

            //by kong  获取商家等级

            $user_id = $users_list[$i]['user_id'];
            $res = SellerGrade::whereHas('getMerchantsGrade', function ($query) use ($user_id) {
                $query->where('ru_id', $user_id);
            });
            $grade = $this->baseRepository->getToArrayFirst($res);
            $grade['grade_name'] = $grade['grade_name'] ?? '';
            $grade['grade_img'] = $grade['grade_img'] ?? '';

            $users_list[$i]['grade_name'] = $grade['grade_name'];

            $review_status = SellerShopinfo::where('ru_id', $users_list[$i]['user_id'])->value('review_status');
            $review_status = $review_status ? $review_status : 0;

            $users_list[$i]['review_status'] = $GLOBALS['_LANG']['not_audited'];
            if ($review_status == 2) {
                $users_list[$i]['review_status'] = $GLOBALS['_LANG']['audited_not_adopt'];
            } elseif ($review_status == 3) {
                $users_list[$i]['review_status'] = $GLOBALS['_LANG']['audited_yes_adopt'];
            }

            $field = MerchantsStepsFields::where('company_type', '<>', '')->count();
            if ($field > 0) {
                $users_list[$i]['company_type'] = MerchantsStepsFields::where('user_id', $users_list[$i]['user_id'])->value('company_type');
                $users_list[$i]['company_type'] = $users_list[$i]['company_type'] ? $users_list[$i]['company_type'] : 0;
            }

            $users_list[$i]['authorizeFile'] = $this->dscRepository->getImagePath($users_list[$i]['authorizeFile']);
            $users_list[$i]['grade_img'] = isset($grade['grade_img']) ? $this->dscRepository->getImagePath($grade['grade_img']) : '';
        }

        $arr = ['users_list' => $users_list, 'filter' => $filter,
            'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']];

        return $arr;
    }

    //获取会员申请商家入驻信息 start -- zhuo
    public function getStepsUserShopInfoList($user_id = 0, $ec_shop_bid = 0, $action = 'add_shop')
    {

        $res = MerchantsStepsProcess::where('process_steps', '<>', 1)
            ->where('is_show', 1)
            ->where('id', '<>', 10)
            ->orderBy('process_steps');
        $res = $this->baseRepository->getToArrayGet($res);
        $arr = [];
        foreach ($res as $key => $row) {
            $arr[$key]['sp_id'] = $row['id'];
            $arr[$key]['process_title'] = $row['process_title'];
            $arr[$key]['steps_title'] = $this->getUserStepsTitle($row['id'], $user_id, $ec_shop_bid, $action);
        }
        return $arr;
    }

    public function getUserStepsTitle($id = 0, $user_id, $ec_shop_bid, $action = 'add_shop')
    {

        $copy_user_id = $user_id;
        if ($action == 'copy_shop') {
            $copy_user_id = 0;
        }

        $res = MerchantsStepsTitle::where('fields_steps', $id);
        $res = $this->baseRepository->getToArrayGet($res);

        $arr = [];
        foreach ($res as $key => $row) {
            $arr[$key]['tid'] = $row['tid'];
            $arr[$key]['fields_titles'] = $row['fields_titles'];
            $arr[$key]['steps_style'] = $row['steps_style'];
            $arr[$key]['titles_annotation'] = $row['titles_annotation'];

            $m_res = MerchantsStepsFieldsCentent::where('tid', $row['tid']);
            $centent = $this->baseRepository->getToArrayFirst($m_res);

            $cententFields = [];
            if (!empty($centent)) {
                $cententFields = get_fields_centent_info($centent['id'], $centent['textFields'], $centent['fieldsDateType'], $centent['fieldsLength'], $centent['fieldsNotnull'], $centent['fieldsFormName'], $centent['fieldsCoding'], $centent['fieldsForm'], $centent['fields_sort'], $centent['will_choose'], 'root', $copy_user_id);
            }
            $arr[$key]['cententFields'] = get_array_sort($cententFields, 'fields_sort');

            //店铺类型、 可经营类目---信息表
            $msi_res = MerchantsShopInformation::where('user_id', $user_id);

            $shop_info = $this->baseRepository->getToArrayFirst($msi_res);

            //初始化店铺信息
            if ($action == 'copy_shop') {
                $shop_info['rz_shopName'] = '';
                $shop_info['hopeLoginName'] = '';
            }

            $parent = [];

            //品牌表
            $msb_res = MerchantsShopBrand::where('user_id', $user_id)->where('bid', $ec_shop_bid);
            $brand_info = $this->baseRepository->getToArrayFirst($msb_res);


            if ($row['steps_style'] == 1) {
                $parent = [//店铺类型数据插入
                    'shoprz_type' => isset($shop_info['shoprz_type']) && !empty($shop_info['shoprz_type']) ? $shop_info['shoprz_type'] : '',
                    'subShoprz_type' => isset($shop_info['subShoprz_type']) && !empty($shop_info['subShoprz_type']) ? $shop_info['subShoprz_type'] : '',
                    'shop_permanent' => isset($shop_info['shop_permanent']) && !empty($shop_info['shop_permanent']) ? $shop_info['shop_permanent'] : '',
                    'authorizeFile' => isset($shop_info['authorizeFile']) && !empty($shop_info['authorizeFile']) ? $this->dscRepository->getImagePath($shop_info['authorizeFile']) : '',
                    'shop_hypermarketFile' => isset($shop_info['shop_hypermarketFile']) && !empty($shop_info['shop_hypermarketFile']) ? $shop_info['shop_hypermarketFile'] : '',
                    'shop_categoryMain' => isset($shop_info['shop_categoryMain']) && !empty($shop_info['shop_categoryMain']) ? $shop_info['shop_categoryMain'] : '',
                    'shop_expireDateStart' => isset($shop_info['shop_expireDateStart']) && !empty($shop_info['shop_expireDateStart']) ? $shop_info['shop_expireDateStart'] : '',
                    'shop_expireDateEnd' => isset($shop_info['shop_expireDateEnd']) && !empty($shop_info['shop_expireDateEnd']) ? $shop_info['shop_expireDateEnd'] : '',
                ];
            } elseif ($row['steps_style'] == 2) { //一级类目列表
                $arr[$key]['first_cate'] = get_first_cate_list('', '', '', $user_id);

                $parent = [
                    'shop_categoryMain' => isset($shop_info['shop_categoryMain']) ? $shop_info['shop_categoryMain'] : ''
                ];
            } elseif ($row['steps_style'] == 3) { //品牌列表

                //复制店铺时   复制店铺品牌
                if ($action == 'copy_shop') {
                    copy_septs_shop_brand_list($user_id);
                }
                $arr[$key]['brand_list'] = get_septs_shop_brand_list($copy_user_id); //品牌列表

                $brandfile_list = get_shop_brandfile_list($ec_shop_bid);
                $arr[$key]['brandfile_list'] = $brandfile_list;

                if (!empty($brand_info['brandEndTime'])) {
                    $brand_info['brandEndTime'] = $this->timeRepository->getLocalDate("Y-m-d H:i", $brand_info['brandEndTime']);
                } else {
                    $brand_info['brandEndTime'] = '';
                }

                $parent = [
                    'bank_name_letter' => isset($brand_info['bank_name_letter']) ? $brand_info['bank_name_letter'] : '',
                    'brandName' => isset($brand_info['brandName']) ? $brand_info['brandName'] : '',
                    'brandFirstChar' => isset($brand_info['brandFirstChar']) ? $brand_info['brandFirstChar'] : '',
                    'brandLogo' => isset($brand_info['brandLogo']) ? $this->dscRepository->getImagePath($brand_info['brandLogo']) : '',
                    'brandType' => isset($brand_info['brandType']) ? $brand_info['brandType'] : '',
                    'brand_operateType' => isset($brand_info['brand_operateType']) ? $brand_info['brand_operateType'] : '',
                    'brandEndTime' => isset($brand_info['brandEndTime']) ? $brand_info['brandEndTime'] : '',
                    'brandEndTime_permanent' => isset($brand_info['brandEndTime_permanent']) ? $brand_info['brandEndTime_permanent'] : ''
                ];
            } elseif ($row['steps_style'] == 4) {
                $msb_res = MerchantsShopBrand::where('user_id', $user_id);
                $brand_list = $this->baseRepository->getToArrayGet($msb_res);

                $arr[$key]['brand_list'] = $brand_list;

                //卖场-入驻地区
                $belong_region = [];
                $shop_info['region_id'] = isset($shop_info['region_id']) && !empty($shop_info['region_id']) ? $shop_info['region_id'] : 0;

                $belong_region['region_id'] = $shop_info['region_id'];
                $belong_region['region_level'] = get_region_level($shop_info['region_id']);
                $belong_region['country_list'] = get_regions_steps();
                $belong_region['province_list'] = get_regions_steps(1, 1);
                $belong_region['city_list'] = isset($belong_region['region_level'][1]) ? get_regions_steps(2, $belong_region['region_level'][1]) : '';
                $arr[$key]['belong_region'] = $belong_region;

                $parent = [
                    'shoprz_brandName' => isset($shop_info['shoprz_brandName']) && !empty($shop_info['shoprz_brandName']) ? $shop_info['shoprz_brandName'] : '',
                    'shop_class_keyWords' => isset($shop_info['shop_class_keyWords']) && !empty($shop_info['shop_class_keyWords']) ? $shop_info['shop_class_keyWords'] : '',
                    'shopNameSuffix' => isset($shop_info['shopNameSuffix']) && !empty($shop_info['shopNameSuffix']) ? $shop_info['shopNameSuffix'] : '',
                    'rz_shopName' => isset($shop_info['rz_shopName']) && !empty($shop_info['rz_shopName']) ? $shop_info['rz_shopName'] : '',
                    'hopeLoginName' => isset($shop_info['hopeLoginName']) && !empty($shop_info['hopeLoginName']) ? $shop_info['hopeLoginName'] : '',
                    'region_id' => $shop_info['region_id'] //卖场-入驻地区
                ];
                if (isset($shop_info['shoprz_type']) && !empty($shop_info['shoprz_type'])) {
                    switch ($shop_info['shoprz_type']) {
                        case 1:
                            $shop_info['shoprz_type'] = $GLOBALS['_LANG']['flagship_store'];
                            break;
                        case 2:
                            $shop_info['shoprz_type'] = $GLOBALS['_LANG']['exclusive_shop'];
                            break;
                        case 3:
                            $shop_info['shoprz_type'] = $GLOBALS['_LANG']['franchised_store'];
                            break;
                        case 4:
                            $shop_info['shoprz_type'] = $GLOBALS['_LANG']['shop_store'];
                            break;
                        default:
                            $shop_info['shoprz_type'];
                    }
                    $parent['shoprz_type'] = $shop_info['shoprz_type'];
                }

            }

            $arr[$key]['parentType'] = $parent; //自定义显示
        }
        return $arr;
    }

    //获取会员申请商家入驻信息 end -- zhuo

    //更新申请商家信息 start
    public function getAdminMerchantsStepsTitle($user_id = 0, $addImg = '')
    {
        $image = new Image(['bgcolor' => $GLOBALS['_CFG']['bgcolor']]);

        $res = MerchantsStepsTitle::groupBy('tid');
        $res = $this->baseRepository->getToArrayGet($res);

        $ec_shop_bid = isset($_REQUEST['ec_shop_bid']) ? trim($_REQUEST['ec_shop_bid']) : 0;
        $ec_shoprz_type = isset($_POST['ec_shoprz_type']) ? intval($_POST['ec_shoprz_type']) : 0;
        $ec_subShoprz_type = isset($_POST['ec_subShoprz_type']) ? intval($_POST['ec_subShoprz_type']) : 0;
        $ec_shop_expireDateStart = isset($_POST['ec_shop_expireDateStart']) ? trim($_POST['ec_shop_expireDateStart']) : '';
        $ec_shop_expireDateEnd = isset($_POST['ec_shop_expireDateEnd']) ? trim($_POST['ec_shop_expireDateEnd']) : '';
        $ec_shop_permanent = isset($_POST['ec_shop_permanent']) ? intval($_POST['ec_shop_permanent']) : 0;
        $ec_shop_categoryMain = isset($_POST['ec_shop_categoryMain']) ? intval($_POST['ec_shop_categoryMain']) : 0;

        //品牌基本信息
        $bank_name_letter = isset($_POST['ec_bank_name_letter']) ? trim($_POST['ec_bank_name_letter']) : '';
        $brandName = isset($_POST['ec_shoprz_brandName']) ? trim($_POST['ec_shoprz_brandName']) : '';
        $brandFirstChar = isset($_POST['ec_brandFirstChar']) ? trim($_POST['ec_brandFirstChar']) : '';

        $brandLogo = '';
        if (isset($_FILES['ec_brandLogo']) && !empty($_FILES['ec_brandLogo'])) {
            $brandLogo = $image->upload_image($_FILES['ec_brandLogo'], 'septs_image');  //图片存放地址 -- data/septs_image

            $this->dscRepository->getOssAddFile([$brandLogo]);
        }

        $brandType = isset($_POST['ec_brandType']) ? intval($_POST['ec_brandType']) : 0;
        $brand_operateType = isset($_POST['ec_brand_operateType']) ? intval($_POST['ec_brand_operateType']) : 0;
        $brandEndTime = isset($_POST['ec_brandEndTime']) ? trim($_POST['ec_brandEndTime']) : '';
        $brandEndTime_permanent = isset($_POST['ec_brandEndTime_permanent']) ? intval($_POST['ec_brandEndTime_permanent']) : 0;

        //品牌资质证件
        $qualificationNameInput = isset($_POST['ec_qualificationNameInput']) ? $_POST['ec_qualificationNameInput'] : [];
        $qualificationImg = isset($_FILES['ec_qualificationImg']) ? $_FILES['ec_qualificationImg'] : [];
        $expiredDateInput = isset($_POST['ec_expiredDateInput']) ? $_POST['ec_expiredDateInput'] : [];
        $b_fid = isset($_POST['b_fid']) ? $_POST['b_fid'] : [];

        //店铺命名信息
        $ec_shoprz_brandName = isset($_POST['ec_shoprz_brandName']) ? $_POST['ec_shoprz_brandName'] : '';
        $ec_shop_class_keyWords = isset($_POST['ec_shop_class_keyWords']) ? $_POST['ec_shop_class_keyWords'] : '';
        $ec_shopNameSuffix = isset($_POST['ec_shopNameSuffix']) ? $_POST['ec_shopNameSuffix'] : '';
        $ec_rz_shopName = isset($_POST['ec_rz_shopName']) ? $_POST['ec_rz_shopName'] : '';
        $ec_hopeLoginName = isset($_POST['ec_hopeLoginName']) ? $_POST['ec_hopeLoginName'] : '';
        $region_id = isset($_POST['rs_city_id']) ? intval($_POST['rs_city_id']) : 0; //卖场-入驻地区

        $time = $this->timeRepository->getGmTime();

        $arr = [];
        foreach ($res as $key => $row) {
            $shop_id = MerchantsShopInformation::where('user_id', $user_id)->value('shop_id');
            $shop_id = $shop_id ? $shop_id : 0;

            $arr[$key]['tid'] = $row['tid'];

            //自定义表单数据插入 start
            $msc_res = MerchantsStepsFieldsCentent::where('tid', $row['tid']);
            $centent = $this->baseRepository->getToArrayFirst($msc_res);

            $cententFields = [];
            if (count($centent) > 0) {
                $cententFields = get_fields_centent_info($centent['id'], $centent['textFields'], $centent['fieldsDateType'], $centent['fieldsLength'], $centent['fieldsNotnull'], $centent['fieldsFormName'], $centent['fieldsCoding'], $centent['fieldsForm'], $centent['fields_sort'], $centent['will_choose'], 'root', $user_id);
            }
            $arr[$key]['cententFields'] = get_array_sort($cententFields, 'fields_sort');

            //店铺类型、 可经营类目---信息表
            $msi_res = MerchantsShopInformation::where('user_id', $user_id);
            $shop_info = $this->baseRepository->getToArrayFirst($msi_res);

            //品牌表
            $msb_res = MerchantsShopBrand::where('user_id', $user_id)->where('bid', $ec_shop_bid);
            $brand_info = $this->baseRepository->getToArrayFirst($msb_res);

            if ($row['steps_style'] == 1) {
                if (isset($_FILES['ec_authorizeFile'])) {
                    $ec_authorizeFile = $image->upload_image($_FILES['ec_authorizeFile'], 'septs_image');  //图片存放地址 -- data/septs_image

                    $this->dscRepository->getOssAddFile([$ec_authorizeFile]);
                }
                $ec_authorizeFile = empty($ec_authorizeFile) ? $shop_info['authorizeFile'] ?? '' : $ec_authorizeFile;
                if (isset($_FILES['ec_authorizeFile'])) {
                    $ec_shop_hypermarketFile = $image->upload_image($_FILES['ec_shop_hypermarketFile'], 'septs_image');  //图片存放地址 -- data/septs_image
                    $this->dscRepository->getOssAddFile([$ec_shop_hypermarketFile]);
                }
                $ec_shop_hypermarketFile = empty($ec_shop_hypermarketFile) ? $shop_info['shop_hypermarketFile'] ?? '' : $ec_shop_hypermarketFile;

                if ($ec_shop_permanent != 1) {
                    $ec_shop_expireDateStart = empty($ec_shop_expireDateStart) ? $shop_info['shop_expireDateStart'] ?? '' : $ec_shop_expireDateStart;
                    $ec_shop_expireDateEnd = empty($ec_shop_expireDateEnd) ? $shop_info['shop_expireDateEnd'] ?? '' : $ec_shop_expireDateEnd;

                    if (!empty($ec_shop_expireDateStart) || !empty($ec_shop_expireDateEnd)) {
                        $ec_shop_expireDateStart = $this->timeRepository->getLocalStrtoTime($ec_shop_expireDateStart);
                        $ec_shop_expireDateEnd = $this->timeRepository->getLocalStrtoTime($ec_shop_expireDateEnd);
                    }
                } else {
                    $ec_shop_expireDateStart = '';
                    $ec_shop_expireDateEnd = '';
                }

                //判断数据是否存在，如果存在则引用 start
                if ($ec_shoprz_type == 0) {
                    $ec_shoprz_type = $shop_info['shoprz_type'] ?? 0;
                }
                if ($ec_subShoprz_type == 0) {
                    $ec_subShoprz_type = $shop_info['subShoprz_type'] ?? 0;
                }
                if ($ec_shop_categoryMain == 0) {
                    $ec_shop_categoryMain = $shop_info['shop_categoryMain'] ?? 0;
                }
                //判断数据是否存在，如果存在则引用 end

                $parent = [//店铺类型数据插入
                    'user_id' => $user_id,
                    'shoprz_type' => $ec_shoprz_type,
                    'subShoprz_type' => $ec_subShoprz_type,
                    'shop_expireDateStart' => $ec_shop_expireDateStart,
                    'shop_expireDateEnd' => $ec_shop_expireDateEnd,
                    'shop_permanent' => $ec_shop_permanent,
                    'authorizeFile' => $ec_authorizeFile,
                    'shop_hypermarketFile' => $ec_shop_hypermarketFile,
                    'shop_categoryMain' => $ec_shop_categoryMain
                ];

                if ($user_id > 0) {
                    if ($shop_id > 0) {
                        if ($parent['shop_expireDateStart'] == '' || $parent['shop_expireDateEnd'] == '') {
                            if ($ec_shop_permanent != 1) {
                                if (isset($shop_info['shop_permanent']) && $shop_info['shop_permanent'] == 1) {
                                    $parent['shop_permanent'] = $shop_info['shop_permanent'];
                                }
                            }
                        }

                        if (empty($parent['authorizeFile'])) {
                            $parent['shop_permanent'] = 0;
                        } else {
                            if ($parent['shop_expireDateStart'] == '' || $parent['shop_expireDateEnd'] == '') {
                                $parent['shop_permanent'] = 1;
                                $parent['shop_expireDateStart'] = '';
                                $parent['shop_expireDateEnd'] = '';
                            }
                        }

                        MerchantsShopInformation::where('user_id', $user_id)->update($parent);
                    } else {
                        $parent['add_time'] = $time;

                        MerchantsShopInformation::insert($parent);
                    }
                }

                if ($ec_shop_permanent == 0) {
                    if ($parent['shop_expireDateStart'] != '') {
                        $parent['shop_expireDateStart'] = $shop_info['shop_expireDateStart'] ?? '';
                    }
                    if ($parent['shop_expireDateEnd'] != '') {
                        $parent['shop_expireDateEnd'] = $shop_info['shop_expireDateEnd'] ?? '';
                    }
                }
            } elseif ($row['steps_style'] == 2) { //一级类目列表
                //2014-11-19 start
                if ($user_id > 0) {
                    if ($shop_id < 1) {
                        $parent['user_id'] = $user_id;
                        $parent['shop_categoryMain'] = $ec_shop_categoryMain;
                        $parent['add_time'] = $time;
                        MerchantsShopInformation::insert($parent);
                    }
                }
                //2014-11-19 end

                $arr[$key]['first_cate'] = get_first_cate_list('', '', '', $user_id);
                $catId_array = get_catId_array($user_id);

                $parent['user_shopMain_category'] = implode('-', $catId_array);

                //2014-11-19 start
                if ($ec_shop_categoryMain == 0) {
                    $ec_shop_categoryMain = $shop_info['shop_categoryMain'] ?? 0;
                    $parent['shop_categoryMain'] = $ec_shop_categoryMain;
                }
                $parent['shop_categoryMain'] = $ec_shop_categoryMain;
                //2014-11-19 end
                MerchantsShopInformation::where('user_id', $user_id)->update($parent);

                if (!empty($parent['user_shopMain_category'])) {
                    get_update_temporarydate_isAdd($catId_array, $user_id);
                }

                get_update_temporarydate_isAdd($catId_array, $user_id, 1);
            } elseif ($row['steps_style'] == 3) { //品牌列表
                $arr[$key]['brand_list'] = get_septs_shop_brand_list($user_id); //品牌列表

                if ($ec_shop_bid > 0) { //更新品牌数据
                    $bank_name_letter = empty($bank_name_letter) ? $brand_info['bank_name_letter'] : $bank_name_letter;
                    $brandName = empty($brandName) ? $brand_info['brandName'] : $brandName;
                    $brandFirstChar = empty($brandFirstChar) ? $brand_info['brandFirstChar'] : $brandFirstChar;
                    $brandLogo = empty($brandLogo) ? $brand_info['brandLogo'] : $brandLogo;
                    $brandType = empty($brandType) ? $brand_info['brandType'] : $brandType;
                    $brand_operateType = empty($brand_operateType) ? $brand_info['brand_operateType'] : $brand_operateType;
                    $brandEndTime = empty($brandEndTime) ? $brand_info['brandEndTime'] : $this->timeRepository->getLocalStrtoTime($brandEndTime);
                    $brandEndTime_permanent = empty($brandEndTime_permanent) ? $brand_info['brandEndTime_permanent'] : $brandEndTime_permanent;

                    $brandfile_list = get_shop_brandfile_list($ec_shop_bid);
                    $arr[$key]['brandfile_list'] = $brandfile_list;

                    $parent = [
                        'user_id' => $user_id,
                        'bank_name_letter' => $bank_name_letter,
                        'brandName' => $brandName,
                        'brandFirstChar' => $brandFirstChar,
                        'brandLogo' => $brandLogo,
                        'brandType' => $brandType,
                        'brand_operateType' => $brand_operateType,
                        'brandEndTime' => $brandEndTime,
                        'brandEndTime_permanent' => $brandEndTime_permanent
                    ];

                    if (!empty($parent['brandEndTime'])) {
                        $arr[$key]['parentType']['brandEndTime'] = $this->timeRepository->getLocalDate("Y-m-d H:i", $parent['brandEndTime']); //输出
                    }

                    if ($user_id > 0 || $addImg == 'addImg') {
                        if ($parent['brandEndTime_permanent'] == 1) {
                            $parent['brandEndTime'] = '';
                        }

                        MerchantsShopBrand::where('user_id', $user_id)
                            ->where('bid', $ec_shop_bid)
                            ->update($parent);

                        get_shop_brand_file($qualificationNameInput, $qualificationImg, $expiredDateInput, $b_fid, $ec_shop_bid); //品牌资质文件上传
                    }
                } else { //插入品牌数据
                    if ($user_id > 0 || $addImg == 'addImg') {//by kong 改
                        $bRes = MerchantsShopBrand::where('brandName', $brandName)
                            ->where('user_id', $user_id)
                            ->value('bid');
                        $bRes = $bRes ? $bRes : 0;

                        if (!$bRes) {
                            $parent = [
                                'user_id' => $user_id,
                                'bank_name_letter' => $bank_name_letter,
                                'brandName' => $brandName,
                                'brandFirstChar' => $brandFirstChar,
                                'brandLogo' => $brandLogo,
                                'brandType' => $brandType,
                                'brand_operateType' => $brand_operateType,
                                'brandEndTime' => $brandEndTime,
                                'brandEndTime_permanent' => $brandEndTime_permanent
                            ];

                            $bid = MerchantsShopBrand::insertGetId($parent);

                            get_shop_brand_file($qualificationNameInput, $qualificationImg, $expiredDateInput, $b_fid, $bid); //品牌资质文件上传
                        }
                    }
                }
            } elseif ($row['steps_style'] == 4) {
                $msb_res = MerchantsShopBrand::where('user_id', $user_id);
                $brand_list = $this->baseRepository->getToArrayGet($msb_res);

                $arr[$key]['brand_list'] = $brand_list;

                $ec_shoprz_brandName = empty($ec_shoprz_brandName) ? $shop_info['shoprz_brandName'] ?? '' : $ec_shoprz_brandName;
                $ec_shop_class_keyWords = empty($ec_shop_class_keyWords) ? $shop_info['shop_class_keyWords'] ?? '' : $ec_shop_class_keyWords;
                $ec_shopNameSuffix = empty($ec_shopNameSuffix) ? $shop_info['shopNameSuffix'] ?? '' : $ec_shopNameSuffix;
                $ec_rz_shopName = empty($ec_rz_shopName) ? $shop_info['rz_shopName'] ?? '' : $ec_rz_shopName;
                $ec_hopeLoginName = empty($ec_hopeLoginName) ? $shop_info['hopeLoginName'] ?? '' : $ec_hopeLoginName;
                $region_id = empty($region_id) ? $shop_info['region_id'] ?? 0 : $region_id; //卖场-入驻地区

                //卖场-入驻地区
                $belong_region = [];
                $belong_region['region_id'] = $region_id;
                $belong_region['region_level'] = get_region_level($region_id);
                $belong_region['country_list'] = get_regions_steps();
                $belong_region['province_list'] = get_regions_steps(1, 1);
                $belong_region['city_list'] = isset($belong_region['region_level'][1]) ? get_regions_steps(2, $belong_region['region_level'][1]) : '';
                $arr[$key]['belong_region'] = $belong_region;

                if (!empty($ec_rz_shopName)) {
                    $parent = [
                        'shoprz_brandName' => $ec_shoprz_brandName,
                        'shop_class_keyWords' => $ec_shop_class_keyWords,
                        'shopNameSuffix' => $ec_shopNameSuffix,
                        'rz_shopName' => $ec_rz_shopName,
                        'hopeLoginName' => $ec_hopeLoginName,
                        'region_id' => $region_id //卖场-入驻地区
                    ];

                    if ($user_id > 0) {
                        if ($shop_id > 0) {
                            MerchantsShopInformation::where('user_id', $user_id)->update($parent);
                        } else {
                            $parent['add_time'] = $time;
                            MerchantsShopInformation::insert($parent);
                        }
                    }
                }
            }
        }

        return $arr;
    }

    public function getAdminStepsTitleInsertForm($user_id)
    {
        $steps_title = $this->getAdminMerchantsStepsTitle($user_id);

        $arr = [
            'formName' => ''
        ];

        for ($i = 0; $i < count($steps_title); $i++) {
            if (is_array($steps_title[$i]['cententFields'])) {
                $cententFields = $steps_title[$i]['cententFields'];
                for ($j = 1; $j <= count($cententFields); $j++) {
                    $arr['formName'] .= $cententFields[$j]['textFields'] . ',';
                }
            }
        }

        $arr['formName'] = substr($arr['formName'], 0, -1);

        return $arr;
    }
    //更新申请商家信息 end

    //获取会员信息列表
    public function getSearchUserList($user_list)
    {
        $html = '';
        if ($user_list) {
            $html .= "<ul>";

            foreach ($user_list as $key => $user) {
                $html .= "<li data-name='" . $user['user_name'] . "' data-id='" . $user['user_id'] . "'>" . $user['user_name'] . "</li>";
            }

            $html .= '</ul>';
        } else {
            $html = '<span class="red">' . $GLOBALS['_LANG']['query_wu_user'] . '</span><input name="user_id" value="0" type="hidden" />';
        }

        return $html;
    }

    // 商家等级申请记录
    public function getSellerApplyInfo($user_id)
    {
        $res = SellerApplyInfo::where('ru_id', $user_id);
        $row = $this->baseRepository->getToArrayFirst($res);

        return $row;
    }
}
