<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class Plugins
 */
class Plugins extends Model
{
    protected $table = 'plugins';

    protected $primaryKey = 'code';

    public $timestamps = false;

    protected $fillable = [
        'version',
        'library',
        'assign',
        'install_date'
    ];

    protected $guarded = [];


    /**
     * @return mixed
     */
    public function getVersion()
    {
        return $this->version;
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
    public function getAssign()
    {
        return $this->assign;
    }

    /**
     * @return mixed
     */
    public function getInstallDate()
    {
        return $this->install_date;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setVersion($value)
    {
        $this->version = $value;
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
    public function setAssign($value)
    {
        $this->assign = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setInstallDate($value)
    {
        $this->install_date = $value;
        return $this;
    }
}
