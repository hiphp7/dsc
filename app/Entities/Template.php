<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class Template
 */
class Template extends Model
{
    protected $table = 'template';

    protected $primaryKey = 'tid';

    public $timestamps = false;

    protected $fillable = [
        'filename',
        'region',
        'library',
        'sort_order',
        'number',
        'type',
        'theme',
        'remarks',
        'floor_tpl'
    ];

    protected $guarded = [];


    /**
     * @return mixed
     */
    public function getFilename()
    {
        return $this->filename;
    }

    /**
     * @return mixed
     */
    public function getRegion()
    {
        return $this->region;
    }

    /**
     * @return mixed
     */
    public function getLibrary()
    {
        return $this->library;
    }

    /**
     * @return mixed
     */
    public function getSortOrder()
    {
        return $this->sort_order;
    }

    /**
     * @return mixed
     */
    public function getNumber()
    {
        return $this->number;
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
    public function getRemarks()
    {
        return $this->remarks;
    }

    /**
     * @return mixed
     */
    public function getFloorTpl()
    {
        return $this->floor_tpl;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setFilename($value)
    {
        $this->filename = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setRegion($value)
    {
        $this->region = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setLibrary($value)
    {
        $this->library = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setSortOrder($value)
    {
        $this->sort_order = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setNumber($value)
    {
        $this->number = $value;
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
    public function setRemarks($value)
    {
        $this->remarks = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setFloorTpl($value)
    {
        $this->floor_tpl = $value;
        return $this;
    }
}
