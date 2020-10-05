<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class TemplatesLeft
 */
class TemplatesLeft extends Model
{
    protected $table = 'templates_left';

    public $timestamps = false;

    protected $fillable = [
        'ru_id',
        'seller_templates',
        'bg_color',
        'img_file',
        'if_show',
        'bgrepeat',
        'align',
        'type',
        'theme',
        'fileurl'
    ];

    protected $guarded = [];


    /**
     * @return mixed
     */
    public function getRuId()
    {
        return $this->ru_id;
    }

    /**
     * @return mixed
     */
    public function getSellerTemplates()
    {
        return $this->seller_templates;
    }

    /**
     * @return mixed
     */
    public function getBgColor()
    {
        return $this->bg_color;
    }

    /**
     * @return mixed
     */
    public function getImgFile()
    {
        return $this->img_file;
    }

    /**
     * @return mixed
     */
    public function getIfShow()
    {
        return $this->if_show;
    }

    /**
     * @return mixed
     */
    public function getBgrepeat()
    {
        return $this->bgrepeat;
    }

    /**
     * @return mixed
     */
    public function getAlign()
    {
        return $this->align;
    }

    /**
     * @return mixed
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @return mixed
     */
    public function getTheme()
    {
        return $this->theme;
    }

    /**
     * @return mixed
     */
    public function getFileurl()
    {
        return $this->fileurl;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setRuId($value)
    {
        $this->ru_id = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setSellerTemplates($value)
    {
        $this->seller_templates = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setBgColor($value)
    {
        $this->bg_color = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setImgFile($value)
    {
        $this->img_file = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setIfShow($value)
    {
        $this->if_show = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setBgrepeat($value)
    {
        $this->bgrepeat = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setAlign($value)
    {
        $this->align = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setType($value)
    {
        $this->type = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setTheme($value)
    {
        $this->theme = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setFileurl($value)
    {
        $this->fileurl = $value;
        return $this;
    }
}
