<?php

namespace App\Http\Controllers;

use App\Libraries\Captcha;
use App\Libraries\CaptchaVerify;
use App\Libraries\Image;
use App\Models\Comment;
use App\Models\CommentImg;
use App\Models\CommentSeller;
use App\Models\Goods;
use App\Models\IntelligentWeight;
use App\Models\MerchantsShopInformation;
use App\Models\OrderGoods;
use App\Models\OrderInfo;
use App\Models\Users;
use App\Repositories\Common\DscRepository;
use App\Services\Comment\CommentService;

/**
 * 提交用户评论
 */
class CommentController extends InitController
{
    protected $commentService;
    protected $dscRepository;

    public function __construct(
        DscRepository $dscRepository,
        CommentService $commentService
    )
    {
        $this->commentService = $commentService;
        $this->dscRepository = $dscRepository;
    }

    public function index()
    {
        if (!request()->exists('cmt') && !request()->exists('act')) {
            /* 只有在没有提交评论内容以及没有act的情况下才跳转 */
            return dsc_header("Location: ./\n");
        }

        $cmt = json_str_iconv(request()->input('cmt'));

        $act = addslashes(trim(request()->input('act')));


        $user_id = session('user_id', 0);

        $result = ['error' => 0, 'message' => '', 'content' => ''];

        if (empty($act)) {
            /*
             * act 参数为空
             * 默认为添加评论内容
             */
            $cmt = dsc_decode($cmt);
            $cmt->page = 1;
            $cmt->id = !empty($cmt->id) ? intval($cmt->id) : 0;
            $cmt->type = !empty($cmt->type) ? intval($cmt->type) : 0;

            if (empty($cmt) || !isset($cmt->type) || !isset($cmt->id)) {
                $result['error'] = 1;
                $result['message'] = $GLOBALS['_LANG']['invalid_comments'];
            } elseif (!is_email($cmt->email)) {
                $result['error'] = 1;
                $result['message'] = $GLOBALS['_LANG']['error_email'];
            } else {
                if ((intval($GLOBALS['_CFG']['captcha']) & CAPTCHA_COMMENT) && gd_version() > 0) {
                    /* 检查验证码 */


                    $validator = new Captcha();
                    if (!$validator->check_word($cmt->captcha)) {
                        $result['error'] = 1;
                        $result['message'] = $GLOBALS['_LANG']['invalid_captcha'];
                    } else {
                        $factor = intval($GLOBALS['_CFG']['comment_factor']);
                        if ($cmt->type == 0 && $factor > 0) {
                            /* 只有商品才检查评论条件 */
                            switch ($factor) {
                                case COMMENT_LOGIN:
                                    if ($user_id == 0) {
                                        $result['error'] = 1;
                                        $result['message'] = $GLOBALS['_LANG']['comment_login'];
                                    }
                                    break;

                                case COMMENT_CUSTOM:
                                    if ($user_id > 0) {
                                        $tmp = OrderInfo::where('user_id', $user_id)
                                            ->whereIn('order_status', [OS_CONFIRMED, OS_SPLITED])
                                            ->whereIn('pay_status', [PS_PAYED, PS_PAYING])
                                            ->whereIn('shipping_status', [SS_SHIPPED, SS_RECEIVED])
                                            ->value('order_id');

                                        if (empty($tmp)) {
                                            $result['error'] = 1;
                                            $result['message'] = $GLOBALS['_LANG']['comment_custom'];
                                        }
                                    } else {
                                        $result['error'] = 1;
                                        $result['message'] = $GLOBALS['_LANG']['comment_custom'];
                                    }
                                    break;
                                case COMMENT_BOUGHT:
                                    if ($user_id > 0) {
                                        $tmp = OrderInfo::where('user_id', $user_id)
                                            ->whereIn('order_status', [OS_CONFIRMED, OS_SPLITED])
                                            ->whereIn('pay_status', [PS_PAYED, PS_PAYING])
                                            ->whereIn('shipping_status', [SS_SHIPPED, SS_RECEIVED])
                                            ->value('order_id');

                                        $goods_id = $cmt->id;
                                        $tmp = $tmp->whereHas('getOrderGoods', function ($query) use ($goods_id) {
                                            $query->where('goods_id', $goods_id);
                                        });

                                        $tmp = $tmp->value('order_id');

                                        if (empty($tmp)) {
                                            $result['error'] = 1;
                                            $result['message'] = $GLOBALS['_LANG']['comment_brought'];
                                        }
                                    } else {
                                        $result['error'] = 1;
                                        $result['message'] = $GLOBALS['_LANG']['comment_brought'];
                                    }
                            }
                        }

                        /* 无错误就保存留言 */
                        if (empty($result['error'])) {
                            $this->commentService->addComment($cmt);
                        }
                    }
                } else {
                    /* 没有验证码时，用时间来限制机器人发帖或恶意发评论 */
                    if (!session()->has('send_time')) {
                        session([
                            'send_time' => 0
                        ]);
                    }

                    $cur_time = gmtime();
                    if (($cur_time - session('send_time')) < 30) { // 小于30秒禁止发评论
                        $result['error'] = 1;
                        $result['message'] = $GLOBALS['_LANG']['cmt_spam_warning'];
                    } else {
                        $factor = intval($GLOBALS['_CFG']['comment_factor']);
                        if ($cmt->type == 0 && $factor > 0) {
                            /* 只有商品才检查评论条件 */
                            switch ($factor) {
                                case COMMENT_LOGIN:
                                    if ($user_id == 0) {
                                        $result['error'] = 1;
                                        $result['message'] = $GLOBALS['_LANG']['comment_login'];
                                    }
                                    break;

                                case COMMENT_CUSTOM:
                                    if ($user_id > 0) {
                                        $tmp = OrderInfo::where('user_id', $user_id)
                                            ->whereIn('order_status', [OS_CONFIRMED, OS_SPLITED])
                                            ->whereIn('pay_status', [PS_PAYED, PS_PAYING])
                                            ->whereIn('shipping_status', [SS_SHIPPED, SS_RECEIVED])
                                            ->value('order_id');

                                        if (empty($tmp)) {
                                            $result['error'] = 1;
                                            $result['message'] = $GLOBALS['_LANG']['comment_custom'];
                                        }
                                    } else {
                                        $result['error'] = 1;
                                        $result['message'] = $GLOBALS['_LANG']['comment_custom'];
                                    }
                                    break;

                                case COMMENT_BOUGHT:
                                    if ($user_id > 0) {
                                        $tmp = OrderInfo::where('user_id', $user_id)
                                            ->whereIn('order_status', [OS_CONFIRMED, OS_SPLITED])
                                            ->whereIn('pay_status', [PS_PAYED, PS_PAYING])
                                            ->whereIn('shipping_status', [SS_SHIPPED, SS_RECEIVED])
                                            ->value('order_id');

                                        $goods_id = $cmt->id;
                                        $tmp = $tmp->whereHas('getOrderGoods', function ($query) use ($goods_id) {
                                            $query->where('goods_id', $goods_id);
                                        });

                                        $tmp = $tmp->value('order_id');

                                        if (empty($tmp)) {
                                            $result['error'] = 1;
                                            $result['message'] = $GLOBALS['_LANG']['comment_brought'];
                                        }
                                    } else {
                                        $result['error'] = 1;
                                        $result['message'] = $GLOBALS['_LANG']['comment_brought'];
                                    }
                            }
                        }
                        /* 无错误就保存留言 */
                        if (empty($result['error'])) {
                            $this->commentService->addComment($cmt);
                            session([
                                'send_time' => $cur_time
                            ]);
                        }
                    }
                }
            }
        }

        /*------------------------------------------------------ */
        //-- 无刷新上传图片ajax
        /*------------------------------------------------------ */
        elseif ($act == 'ajax_return_images') {
            $img_file = isset($_FILES['file']) ? $_FILES['file'] : [];

            $order_id = intval(request()->input('order_id'));
            $rec_id = intval(request()->input('rec_id'));
            $goods_id = intval(request()->input('goods_id'));
            $user_id = intval(request()->input('userId'));

            if (!empty($user_id)) {
                $image = new Image(['bgcolor' => $GLOBALS['_CFG']['bgcolor']]);

                $img_file = $image->upload_image($img_file, 'cmt_img/' . date('Ym')); //原图
                if ($img_file === false) {
                    $result['error'] = 1;
                    $result['msg'] = $image->error_msg();
                    return response()->json($result);
                }

                $img_thumb = $image->make_thumb($img_file, $GLOBALS['_CFG']['single_thumb_width'], $GLOBALS['_CFG']['single_thumb_height'], DATA_DIR . '/cmt_img/' . date('Ym') . '/thumb/'); //缩略图

                $this->dscRepository->getOssAddFile([$img_file, $img_thumb]);

                $img_count = CommentImg::where('user_id', $user_id)
                    ->where('order_id', $order_id)
                    ->where('goods_id', $goods_id)
                    ->count();

                if ($img_count < 10 && $img_file) {
                    $return = [
                        'order_id' => $order_id,
                        'rec_id' => $rec_id,
                        'goods_id' => $goods_id,
                        'user_id' => $user_id,
                        'comment_img' => $img_file,
                        'img_thumb' => $img_thumb
                    ];

                    CommentImg::insert($return);
                } else {
                    $result['error'] = 1;
                    $result['msg'] = $GLOBALS['_LANG']['comment_img_number'];
                    return response()->json($result);
                }
            } else {
                $result['error'] = 2;
                $result['msg'] = $GLOBALS['_LANG']['please_login'];
                return response()->json($result);
            }

            $imgWhere = [
                'user_id' => $user_id,
                'order_id' => $order_id,
                'goods_id' => $goods_id,
                'comment_id' => 0
            ];
            $img_list = $this->commentService->getCommentImgList($imgWhere);

            $result['imglist_count'] = $img_list ? count($img_list) : 0;
            $result['currentImg_path'] = isset($img_list[0]['comment_img']) ? $img_list[0]['comment_img'] : '';
            $result['currentImg_id'] = isset($img_list[0]['id']) ? $img_list[0]['id'] : 0;
            $this->smarty->assign('img_list', $img_list);
            $result['content'] = $this->smarty->fetch("library/comment_image.lbi");

            return response()->json($result);
        }
        /*------------------------------------------------------ */
        //-- 删除晒单�        �片
        /*------------------------------------------------------ */
        elseif ($act == 'del_pictures') {
            $img_id = intval(request()->input('cur_imgId'));
            $order_id = intval(request()->input('order_id'));
            $goods_id = intval(request()->input('goods_id'));

            if (empty($user_id) || !$img_id) {
                $result['error'] = 1;
            }

            $imgWhere = [
                'user_id' => $user_id,
                'order_id' => $order_id,
                'goods_id' => $goods_id
            ];
            $img_list = $this->commentService->getCommentImgList($imgWhere);

            if ($img_list) {
                foreach ($img_list as $key => $val) {
                    if ($img_id == $val['id']) {
                        CommentImg::where('id', $img_id)->delete();

                        unset($img_list[$key]);

                        $this->dscRepository->getOssDelFile([$val['comment_img'], $val['img_thumb']]);

                        @unlink(storage_public($val['comment_img']));
                        @unlink(storage_public($val['img_thumb']));
                    }
                }
            }

            $this->smarty->assign('img_list', $img_list);
            $result['content'] = $this->smarty->fetch("library/comment_image.lbi");

            return response()->json($result);
        }
        /*------------------------------------------------------ */
        //-- 晒单图片列表
        /*------------------------------------------------------ */
        elseif ($act == 'ajax_return_images_list') {
            $order_id = intval(request()->input('order_id'));
            $goods_id = intval(request()->input('goods_id'));

            $imgWhere = [
                'user_id' => $user_id,
                'order_id' => $order_id,
                'goods_id' => $goods_id
            ];
            $img_list = $this->commentService->getCommentImgList($imgWhere);

            if ($img_list) {
                $this->smarty->assign('img_list', $img_list);
                $result['content'] = $this->smarty->fetch("library/comment_image.lbi");
            } else {
                $result['error'] = 1;
            }

            return response()->json($result);
        }

        /*------------------------------------------------------ */
        //-- 晒单图片库处理
        /*------------------------------------------------------ */
        elseif ($act == 'comm_order_goods') {
            $result = ['error' => 0, 'message' => '', 'content' => ''];

            $cmt = strip_tags(urldecode(request()->input('cmt')));
            $cmt = json_str_iconv($cmt);
            if (empty($cmt)) {
                $result['error'] = 1;
                return response()->json($result);
            }

            $cmt = dsc_decode($cmt);

            $comment_id = isset($cmt->comment_id) ? intval($cmt->comment_id) : 0;
            $rank = isset($cmt->comment_rank) ? intval($cmt->comment_rank) : 5;
            $rank_server = 5;
            $rank_delivery = 5;
            $content = isset($cmt->content) ? htmlspecialchars(trim($cmt->content)) : '';
            $order_id = isset($cmt->order_id) ? intval($cmt->order_id) : 0;
            $goods_id = isset($cmt->goods_id) ? intval($cmt->goods_id) : 0;
            $goods_tag = isset($cmt->impression) ? trim($cmt->impression) : '';
            $sign = isset($cmt->sign) ? trim($cmt->sign) : 0;
            $result['sign'] = $sign;
            $rec_id = isset($cmt->rec_id) ? intval($cmt->rec_id) : 0;

            $addtime = gmtime();
            $ip = $this->dscRepository->dscIp();

            $captcha_str = isset($cmt->captcha) ? htmlspecialchars(trim($cmt->captcha)) : '';

            /* 验证码检查 */
            if ((intval($GLOBALS['_CFG']['captcha']) & CAPTCHA_COMMENT) && gd_version() > 0) {
                $verify = app(CaptchaVerify::class);
                $captcha_code = $verify->check($captcha_str, 'user_comment', $rec_id);

                if (!$captcha_code) {
                    $result['error'] = 1;
                    $result['message'] = $GLOBALS['_LANG']['invalid_captcha'];
                    return response()->json($result);
                }
            }

            if (!$comment_id) {
                $status = 1 - $GLOBALS['_CFG']['comment_check'];

                $ru_id = Goods::where('goods_id', $goods_id)->value('user_id');

                if (!empty($user_id)) {
                    $other = [
                        'comment_type' => 0,
                        'id_value' => $goods_id,
                        'email' => session('email', ''),
                        'user_name' => session('user_name', ''),
                        'content' => $content,
                        'comment_rank' => $rank,
                        'comment_server' => $rank_server,
                        'comment_delivery' => $rank_delivery,
                        'add_time' => $addtime,
                        'ip_address' => $ip,
                        'status' => $status,
                        'parent_id' => 0,
                        'user_id' => $user_id,
                        'single_id' => 0,
                        'order_id' => $order_id,
                        'rec_id' => $rec_id,
                        'goods_tag' => $goods_tag,
                        'ru_id' => $ru_id
                    ];

                    $comment_id = Comment::insertGetId($other);
                }

                if ($comment_id) {

                    //更新
                    CommentImg::where('rec_id', $rec_id)->where('goods_id', $goods_id)->where('user_id', $user_id)->update(['comment_id' => $comment_id]);

                    if ($status == 1) {
                        //更新评论数量
                        $res = Goods::where('goods_id', $goods_id)->increment('comments_number', 1);

                        //更新智能权重里的商品评论数
                        if ($res) {
                            IntelligentWeight::where('goods_id', $goods_id)->increment('goods_comment_number', 1);
                        }
                    }

                    $result['message'] = $GLOBALS['_CFG']['comment_check'] ? $GLOBALS['_LANG']['cmt_submit_wait'] : $GLOBALS['_LANG']['cmt_submit_done'];
                    $result['message_type'] = $GLOBALS['_LANG']['Review_information'];
                }
            } else {
                CommentImg::where('rec_id', $rec_id)
                    ->where('goods_id', $goods_id)
                    ->where('user_id', $user_id)
                    ->where('comment_id', 0)
                    ->update(['comment_id' => $comment_id]);

                $result['message'] = $GLOBALS['_LANG']['single_success'];
                $result['message_type'] = $GLOBALS['_LANG']['single_information'];
            }

            return response()->json($result);
        }

        /*------------------------------------------------------ */
        //-- 评论商家满意度
        /*------------------------------------------------------ */
        elseif ($act == 'satisfaction_degree') {
            $result = ['error' => 0, 'msg' => '', 'content' => ''];

            $rank = strip_tags(urldecode(request()->input('rank')));
            $rank = json_str_iconv($rank);

            if (empty($rank)) {
                $result['error'] = 1;
                $result['msg'] = $GLOBALS['_LANG']['parameter_error'];
                return response()->json($result);
            }
            if (empty($user_id)) {
                $result['error'] = 1;
                $result['msg'] = $GLOBALS['_LANG']['please_login'];
                return response()->json($result);
            }

            $cmt = dsc_decode($rank);

            $order_id = isset($cmt->order_id) ? intval($cmt->order_id) : 0;
            $desc_rank = isset($cmt->desc_rank) ? intval($cmt->desc_rank) : 5;
            $service_rank = isset($cmt->service_rank) ? intval($cmt->service_rank) : 5;
            $delivery_rank = isset($cmt->delivery_rank) ? intval($cmt->delivery_rank) : 5;
            $sender_rank = isset($cmt->sender_rank) ? trim($cmt->sender_rank) : '';
            $addtime = gmtime();

            //商家id
            $ru_id = OrderGoods::where('order_id', $order_id)->value('ru_id');

            $other = [
                'user_id' => $user_id,
                'ru_id' => $ru_id,
                'order_id' => $order_id,
                'desc_rank' => $desc_rank,
                'service_rank' => $service_rank,
                'delivery_rank' => $delivery_rank,
                'sender_rank' => $sender_rank,
                'add_time' => $addtime
            ];

            $result = CommentSeller::insertGetId($other);

            if ($result) {

                //获取评论此商家的商品
                $goods_id = OrderGoods::select('goods_id')->where('order_id', $order_id)->get();

                if ($goods_id) {
                    foreach ($goods_id as $gid) {
                        //获取对商家评论的数量
                        $comment_seller_num = CommentSeller::where('order_id', $order_id)->count();
                        $num = ['goods_id' => $gid['goods_id'], 'merchants_comment_number' => $comment_seller_num];
                        update_comment_seller($gid['goods_id'], $num);
                    }
                }

                //插入店铺评分
                $store_score = sprintf("%.2f", ($desc_rank + $service_rank + $delivery_rank) / 3);

                MerchantsShopInformation::where('user_id', $ru_id)->increment('store_score', $store_score);
            }

            if (!$result) {
                $result['error'] = 1;
                $result['msg'] = $GLOBALS['_LANG']['parameter_error'];
            }
            return response()->json($result);
        }

        /*------------------------------------------------------ */
        //-- 商品评论列表
        /*------------------------------------------------------ */
        elseif ($act == 'comment_all' || $act == 'comment_good' || $act == 'comment_middle' || $act == 'comment_short' || $act == 'gotopage') {
            /*
             * act 参数不为空
             * 默认为评论内容列表
             * 根据 _GET 创建一个静态对象
             */

            $id = htmlspecialchars(request()->input('id', 0));
            $type = intval(request()->input('type'));
            $page = intval(request()->input('page', 1));

            $id = explode("|", $id);

            $goods_id = $id[0];
            $cmtType = $id[1];

            $comments = assign_comment($goods_id, $type, $page, $cmtType);

            $this->smarty->assign('comment_type', $type);
            $this->smarty->assign('id', $id);
            $this->smarty->assign('username', session('user_name'));
            $this->smarty->assign('email', session('email'));
            $this->smarty->assign('comments', $comments['comments']);
            $this->smarty->assign('pager', $comments['pager']);

            $this->smarty->assign('count', $comments['count']);
            $this->smarty->assign('size', $comments['size']);

            $result['content'] = $this->smarty->fetch("library/comments_list.lbi");
            return response()->json($result);
        }

        /*------------------------------------------------------ */
        //-- 评论有用+1
        /*------------------------------------------------------ */
        elseif ($act == 'add_useful') {
            $res = ['err_msg' => '', 'content' => '', 'err_no' => 0];

            $id = intval(request()->input('id'));
            $type = request()->input('type', 'comment');
            $goods_id = intval(request()->input('goods_id'));

            if (!empty($id)) {
                if (!isset($user_id) || $user_id == 0) {
                    $res['url'] = get_return_goods_url($goods_id);
                    $res['err_no'] = 1;
                } else {
                    $comment = Comment::select('useful_user', 'useful')->where('comment_id', $id)->first();

                    $comment = $comment ? $comment->toArray() : [];

                    if ($comment && $comment['useful_user']) {
                        $useful_user = explode(',', $comment['useful_user']);
                        if (in_array($user_id, $useful_user)) {
                            $res['err_no'] = 2;
                            return response()->json($res);
                        } else {
                            array_push($useful_user, $user_id);
                            $useful_user = implode(',', $useful_user);
                        }
                    } else {
                        $useful_user = [0];
                        array_push($useful_user, $user_id);
                        $useful_user = implode(',', $useful_user);
                    }

                    $count = Comment::select('useful_user', 'useful')->where('comment_id', $id)->count();

                    if ($count == 1) {
                        $update = Comment::where('comment_id', $id)->increment('useful', 1, ['useful_user' => $useful_user]);

                        if ($update) {
                            $res = ['option' => 'true', 'id' => $id, 'type' => $type, 'useful' => $comment['useful'] + 1, 'err_no' => 0];
                        } else {
                            $res = ['error' => '', 'id' => $id, 'type' => $type, 'err_no' => 2];
                        }
                    } else {
                        $res = ['option' => '', 'id' => $id, 'type' => $type, 'err_no' => 2];
                    }
                }
            }

            return response()->json($res);
        }

        /*------------------------------------------------------ */
        //-- 商品评论回复
        /*------------------------------------------------------ */
        elseif ($act == 'comment_reply') {
            $result = ['err_msg' => '', 'err_no' => 0, 'content' => ''];

            $comment_id = intval(request()->input('comment_id'));
            $reply_content = htmlspecialchars(trim(request()->input('reply_content', 0)));
            $goods_id = intval(request()->input('goods_id'));

            $comment_user = intval(request()->input('user_id'));
            $libType = intval(request()->input('libType'));

            $type = 0;
            $reply_page = 1;

            $add_time = gmtime();
            $real_ip = $this->dscRepository->dscIp();

            $result['comment_id'] = $comment_id;
            $result['reply_content'] = $reply_content;

            if (!isset($user_id) || $user_id == 0) {
                //$result['err_no'] = 1;
            } elseif ($comment_user == $user_id) {
                //$result['err_no'] = 2;
            } else {
                $comment_user_count = Comment::where('id_value', $goods_id)
                    ->where('parent_id', $comment_id)
                    ->where('user_id', $user_id)
                    ->count();

                if ($comment_user_count > 0) {
                    $result['err_no'] = 2;
                } else {
                    $comment_user_name = Users::where('user_id', $user_id)->value('user_name');

                    $status = 1 - $GLOBALS['_CFG']['comment_check'];

                    $other = [
                        'id_value' => $goods_id,
                        'content' => $reply_content,
                        'comment_type' => 2,
                        'user_name' => $comment_user_name,
                        'comment_rank' => 5,
                        'comment_server' => 5,
                        'comment_delivery' => 5,
                        'add_time' => $add_time,
                        'parent_id' => $comment_id,
                        'user_id' => $user_id,
                        'ip_address' => $real_ip,
                        'status' => $status
                    ];
                    Comment::insert($other);

                    $result['message'] = $GLOBALS['_CFG']['comment_check'] ? $GLOBALS['_LANG']['cmt_submit_wait'] : $GLOBALS['_LANG']['cmt_submit_done'];
                }
            }

            if ($libType == 1) {
                $size = 10;
            } else {
                $size = 2;
            }

            if ($result['err_no'] != 1) {
                $reply = $this->commentService->getReplyList($goods_id, $comment_id, $type, $reply_page, $libType, $size);
                $this->smarty->assign('reply_pager', $reply['reply_pager']);
                $this->smarty->assign('reply_count', $reply['reply_count']);
                $this->smarty->assign('reply_list', $reply['reply_list']);
                $this->smarty->assign('lang', $GLOBALS['_LANG']);

                $result['reply_count'] = $reply['reply_count'];

                if ($libType == 1) {
                    $result['content'] = $this->smarty->fetch("library/comment_repay.lbi");
                } else {
                    $result['content'] = $this->smarty->fetch("library/comment_reply.lbi");
                }
            }

            $result['url'] = get_return_goods_url($goods_id);

            return response()->json($result);
        }
    }
}
