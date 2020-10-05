<?php

namespace App\Modules\Suppliers\Controllers;

use App\Http\Controllers\Controller;
use App\Dsctrait\Modules\Suppliers\IniTrait;
use App\Repositories\Common\BaseRepository;

class InitController extends Controller
{
    protected $baseRepository;

    use IniTrait;

    public function __construct(
        BaseRepository $baseRepository
    ) {
        $this->baseRepository = $baseRepository;
    }
}
