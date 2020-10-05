<?php

namespace App\Http\Controllers;

/**
 * 轮播图片程序
 */
class CycleImageController extends InitController
{
    public function index()
    {
        header('Content-Type: application/xml; charset=' . EC_CHARSET);
        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: Thu, 27 Mar 1975 07:38:00 GMT');
        header('Last-Modified: ' . date('r'));
        header('Pragma: no-cache');

        if (file_exists(storage_public(DATA_DIR . '/cycle_image.xml'))) {
            echo file_get_contents(storage_public(DATA_DIR . '/cycle_image.xml'));
        } else {
            echo '<?xml version="1.0" encoding="' . EC_CHARSET . '"?><bcaster><item item_url="images/200609/05.jpg" link="http://www.ecmoban.com" /></bcaster>';
        }
    }
}
