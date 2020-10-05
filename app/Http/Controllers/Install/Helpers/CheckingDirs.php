<?php

namespace App\Http\Controllers\Install\Helpers;

class CheckingDirs
{
    public function getCheckingDirsLang()
    {
        $checking_dirs = [];

        $checking_dirs['public'] = [
            'cert',
            'images',
            'images/upload',
            'images/upload/Image',
            'images/upload/File',
            'images/upload/Flash',
            'images/upload/Media',
            'data',
            'data/afficheimg',
            'data/brandlogo',
            'data/cardimg',
            'data/feedbackimg',
            'data/packimg',
            'data/sqldata'
        ];

        $checking_dirs['framework'] = [
            'temp',
            'temp/backup',
            'temp/caches',
            'temp/compiled',
            'temp/query_caches',
            'temp/static_caches'
        ];

        return $checking_dirs;
    }
}
