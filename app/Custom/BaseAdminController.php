<?php

namespace App\Custom;

use App\Modules\Admin\Controllers\BaseController as FrontController;


class BaseAdminController extends FrontController
{
    use CustomView;
}
