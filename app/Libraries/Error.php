<?php

namespace App\Libraries;

/**
 * 用户级错误处理类
 * Class Error
 * @package App\Services
 */
class Error
{
    public $_message = [];
    public $_template = '';
    public $error_no = 0;

    /**
     * 构造函数
     *
     * @access  public
     * @param string $tpl
     * @return  void
     */
    public function __construct($tpl = 'message.dwt')
    {
        $this->_template = $tpl;
    }

    /**
     * 添加一条错误信息
     *
     * @param string $msg
     * @param int $errno
     */
    public function add($msg = '', $errno = 1)
    {
        if (is_array($msg)) {
            $this->_message = array_merge($this->_message, $msg);
        } else {
            $this->_message[] = $msg;
        }

        $this->error_no = $errno;
    }

    /**
     * 清空错误信息
     *
     * @access  public
     * @return  void
     */
    public function clean()
    {
        $this->_message = [];
        $this->error_no = 0;
    }

    /**
     * 返回所有的错误信息的数组
     *
     * @access  public
     * @return  array
     */
    public function get_all()
    {
        return $this->_message;
    }

    /**
     * 返回最后一条错误信息
     *
     * @access  public
     * @return  void
     */
    public function last_message()
    {
        return array_slice($this->_message, -1);
    }

    /**
     * 返回错误编号
     *
     * @return int
     */
    public function error_no()
    {
        return $this->error_no;
    }

    /**
     * 显示错误信息
     *
     * @access  public
     * @param string $link
     * @param string $href
     * @return  void
     */
    public function show($link = '', $href = '')
    {
        if ($this->error_no > 0) {
            $message = [];

            $link = (empty($link)) ? $GLOBALS['_LANG']['back_up_page'] : $link;
            $href = (empty($href)) ? 'javascript:history.back();' : $href;
            $message['url_info'][$link] = $href;
            $message['back_url'] = $href;

            foreach ($this->_message as $msg) {
                $message['content'] = '<div>' . htmlspecialchars($msg) . '</div>';
            }

            if (isset($GLOBALS['smarty'])) {
                assign_template();
                $GLOBALS['smarty']->assign('auto_redirect', true);
                $GLOBALS['smarty']->assign('message', $message);
                return $GLOBALS['smarty']->display($this->_template);
            } else {
                return $message['content'];
            }
        }
    }
}
