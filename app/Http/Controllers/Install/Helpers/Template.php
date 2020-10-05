<?php

namespace App\Http\Controllers\Install\Helpers;

class Template
{
    /**
     * 用来存储变量的空间
     *
     * @access  private
     * @var     array $vars
     */
    protected $vars = [];

    /**
     * 模板存放的目录路径
     *
     * @access  private
     * @var     string $path
     */
    public $path;

    /**
     * 构造函数
     *
     * @access  public
     * @param string $path
     * @return  void
     */
    public function __construct()
    {
        $this->path = $this->path ? $this->path : '';
        $this->template($this->path);
    }

    /**
     * 构造函数
     *
     * @access  public
     * @param string $path
     * @return  void
     */
    public function template($path)
    {
        $this->path = $path;
    }

    /**
     * 模拟smarty的assign函数
     *
     * @access  public
     * @param string $name 变量的名字
     * @param mix $value 变量的值
     * @return  void
     */
    public function assign($name, $value)
    {
        $this->vars[$name] = $value;
    }

    /**
     * 模拟smarty的fetch函数
     *
     * @access  public
     * @param string $file 文件相对路径
     * @return  string      模板的内容(文本格式)
     */
    public function fetch($file)
    {
        extract($this->vars);
        ob_start();
        include($this->path . $file);
        $contents = ob_get_contents();
        ob_end_clean();

        $contents = preg_replace('/__ROOT__/', __ROOT__, $contents);
        $contents = preg_replace('/__PUBLIC__/', __PUBLIC__, $contents);
        $contents = preg_replace('/__STORAGE__/', __STORAGE__, $contents);

        $contents = preg_replace('/<\/form>/i', csrf_field() . "</form>", $contents);

        $csrf_token = '<meta name="csrf-token" content="' . csrf_token() . '">';
        $contents = preg_replace('/<head>/i', "<head>\n\r" . $csrf_token, $contents);

        return $contents;
    }

    /**
     * 模拟smarty的display函数
     *
     * @access  public
     * @param string $file 文件相对路径
     * @return  void
     */
    public function display($file)
    {
        echo $this->fetch($file);
    }
}
