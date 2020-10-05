<?php

namespace App\Http\Controllers;

use App\Models\Vote;
use App\Models\VoteLog;
use App\Models\VoteOption;
use App\Repositories\Common\DscRepository;

/**
 * 调查程序
 */
class VoteController extends InitController
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
        if (!request()->exists('vote') || !request()->exists('options') || !request()->exists('type')) {
            return dsc_header("Location: ./\n");
        }

        $res = ['error' => 0, 'message' => '', 'content' => ''];

        $vote_id = (int)request()->input('vote', 0);
        $options = addslashes(trim(request()->input('options', '')));

        $ip_address = $this->dscRepository->dscIp();

        if ($this->vote_already_submited($vote_id, $ip_address)) {
            $res['error'] = 1;
            $res['message'] = $GLOBALS['_LANG']['vote_ip_same'];
        } else {
            $this->save_vote($vote_id, $ip_address, $options);

            $vote = get_vote($vote_id);
            if (!empty($vote)) {
                $this->smarty->assign('vote_id', $vote['id']);
                $this->smarty->assign('vote', $vote['content']);
            }

            $str = $this->smarty->fetch("library/vote.lbi");

            $pattern = '/(?:<(\w+)[^>]*> .*?)?<div\s+id="ECS_VOTE">(.*)<\/div>(?:.*?<\/\1>)?/is';

            if (preg_match($pattern, $str, $match)) {
                $res['content'] = $match[2];
            }
            $res['message'] = $GLOBALS['_LANG']['vote_success'];
        }


        return response()->json($res);
    }

    /**
     * 检查是否已经提交过投票
     *
     * @access  private
     * @param integer $vote_id
     * @param string $ip_address
     * @return  boolean
     */
    private function vote_already_submited($vote_id, $ip_address)
    {
        $res = VoteLog::where('ip_address', $ip_address)
            ->where('vote_id', $vote_id);

        $count = $res->count();

        return ($count > 0);
    }

    /**
     * 保存投票结果信息
     *
     * @access  public
     * @param integer $vote_id
     * @param string $ip_address
     * @param string $option_id
     * @return  void
     */
    private function save_vote($vote_id, $ip_address, $option_id)
    {
        $other = [
            'vote_id' => $vote_id,
            'ip_address' => $ip_address,
            'vote_time' => gmtime()
        ];
        VoteLog::insert($other);

        /* 更新投票主题的数量 */
        Vote::where('vote_id', $vote_id)->increment('vote_count', 1);

        /* 更新投票选项的数量 */
        if ($option_id) {
            $option_id = !is_array($option_id) ? explode(",", $option_id) : $option_id;
            VoteOption::whereIn('option_id', $option_id)->increment('option_count', 1);
        }
    }
}
