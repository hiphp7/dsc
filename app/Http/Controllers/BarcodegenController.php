<?php

namespace App\Http\Controllers;

use PicoPrime\BarcodeGen\BarcodeGen;

class BarcodegenController extends InitController
{
    public function index()
    {
        $filetype = request()->input('filetype', 'PNG');
        $scale = request()->input('scale', 1);
        $orientation = 'horizontal';
        $size = request()->input('size', 40);
        $text = request()->input('text', '');
        $codeType = request()->input('code', 'code39');

        $r = BarcodeGen::generate(compact('text', 'size', 'orientation', 'codeType', 'scale'));
        return $r->response($filetype);
    }
}
