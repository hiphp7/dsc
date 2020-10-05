<?php

namespace App\Services\Mail;

use App\Models\MailTemplates;
use App\Repositories\Common\BaseRepository;


class MailTemplateManageService
{
    protected $baseRepository;

    public function __construct(
        BaseRepository $baseRepository
    )
    {
        $this->baseRepository = $baseRepository;
    }


    /**
     * 加载指定的模板内容
     *
     * @access  public
     * @param string $temp 邮件模板的ID
     * @return  array
     */
    public function loadTemplate($temp_id)
    {
        $res = MailTemplates::where('template_id', $temp_id);
        $row = $this->baseRepository->getToArrayFirst($res);
        return $row;
    }
}
