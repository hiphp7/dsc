<?php

namespace App\Http\Controllers;

/**
 * 日历言语
 */
class CalendarController extends InitController
{
    public function index()
    {
        $lang = trim(request()->input('lang', $this->config['lang']));

        if (!file_exists(resource_path('lang/' . $lang . '/calendar.php')) || strrchr($lang, '.')) {
            $lang = $this->config['lang'];
        }

        header('Content-type: application/x-javascript; charset=' . EC_CHARSET);

        if (file_exists(resource_path('lang/' . $lang . '/calendar.php'))) {
            include_once(resource_path('lang/' . $lang . '/calendar.php'));

            foreach ($GLOBALS['_LANG']['calendar_lang'] as $cal_key => $cal_data) {
                echo 'var ' . $cal_key . " = \"" . $cal_data . "\";\r\n";
            }

            include_once(public_path('js/calendar/calendar.min.js'));
        }
    }
}
