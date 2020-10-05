<?php

namespace App\Http\Controllers;

use App\Libraries\Page;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    /**
     * 模板输出变量
     *
     * @var array
     */
    protected $tVar = [];

    protected $pager = [];

    /**
     * Execute an action on the controller.
     *
     * @param string $method
     * @param array $parameters
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function callAction($method, $parameters)
    {
        if (method_exists($this, 'initialize')) {
            $response = call_user_func([$this, 'initialize']);
            if (!is_null($response)) {
                return $response;
            }
        }

        return call_user_func_array([$this, $method], $parameters);
    }

    /**
     * 获取当前模块名
     *
     * @return string
     */
    protected function getCurrentModuleName()
    {
        return $this->getCurrentAction()['module'];
    }

    /**
     * 获取当前控制器名
     *
     * @return string
     */
    protected function getCurrentControllerName()
    {
        return $this->getCurrentAction()['controller'];
    }

    /**
     * 获取当前方法名
     *
     * @return string
     */
    protected function getCurrentMethodName()
    {
        return $this->getCurrentAction()['method'];
    }

    /**
     * 获取当前控制器与方法
     *
     * @return array
     */
    protected function getCurrentAction()
    {
        $action = request()->route()->getAction();

        list($app, $module_path, $module_name) = explode('\\', $action['namespace']);

        $action = str_replace($action['namespace'] . '\\', '', $action['controller']);

        $field = explode('\\', $action);

        if (count($field) > 1) {
            $actions = explode('\\', $action);
            $action = 'Http\\Controllers\\' . $actions[1];
        } else {
            $action = 'Http\\Controllers\\' . $action;
        }

        list($module, $_, $action) = explode('\\', $action);

        list($controller, $action) = explode('@', $action);

        if ($app && $module_path == 'Modules') {
            $module = $module_name; // 获取模块名
        }

        return ['module' => $module, 'controller' => Str::studly($controller), 'method' => $action];
    }

    /**
     * 模板变量赋值
     *
     * @param $name
     * @param string $value
     */
    protected function assign($name, $value = '')
    {
        if (is_array($name)) {
            $this->tVar = array_merge($this->tVar, $name);
        } else {
            $this->tVar[$name] = $value;
        }
    }

    /**
     * 加载模板和页面输出 可以返回输出内容
     * @param string $filename
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    protected function display($filename = '')
    {
        if ($filename) {
            return view($filename, $this->tVar);
        }

        $path = strtolower($this->getCurrentModuleName());

        $controller = str_replace('Controller', '', $this->getCurrentControllerName());

        $method = strtolower(Str::camel($this->getCurrentMethodName()));

        $file = $controller . '.' . str_replace('action', '', $method);

        $filename = $path . '.' . strtolower($file);

        return view($filename, $this->tVar);
    }

    /**
     * ECJia App 快捷登录
     * @desc $package = ['origin', 'usertype', 'openid', 'gmtime', 'sign']
     * @param Request $request
     * @return bool|string
     */
    protected function ecjiaLogin(Request $request)
    {
        if ($request->has('ecjiahash')) {
            $package = $request->get('ecjiahash');

            $data = dsc_decode(base64_decode($package), true);
            $sign = $data['sign'];
            unset($data['sign']);

            // 查询
            $user = DB::table('connect_user')
                ->leftJoin('users', 'users.user_id', '=', 'connect_user.user_id')
                ->where('connect_user.user_type', $data['usertype'])
                ->where('connect_user.open_id', $data['openid'])
                ->where('connect_code', $data['origin'])
                ->orderBy('id', 'DESC')
                ->select('connect_user.*', 'users.user_id', 'users.user_name')
                ->first();

            if (is_null($user)) {
                return false;
            }

            // 授权数据校验
            $data['token'] = collect($user)->get('access_token');
            ksort($data);

            $signed = hash_hmac('md5', http_build_query($data, '', '&'), collect($user)->get('refresh_token'));

            // 检测签名与过期时间5分钟
            if ($signed === $sign && $this->inGmTimeInterval($data['gmtime'], 5)) {

                return $user;
            }
        }

        return false;
    }

    /**
     * 校验时间与当前GMTIME的跨度
     * @param int $time 时间戳
     * @param int $span 跨度(minute)
     * @return bool
     */
    protected function inGmTimeInterval($time = 0, $span = 0)
    {
        $s = abs(gmtime() - $time);

        if ($s / 60 > $span) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * 检测 Referer
     * @return bool
     */
    protected function checkReferer()
    {
        $current_url = request()->url();
        $referer = request()->header('referer');
        $url_host = parse_url($referer, PHP_URL_HOST);

        $host = $url_host ? explode('.', $url_host) : $url_host;
        if (count($host) > 2) {
            $url_host = $host[count($host) - 2] . '.' . $host[count($host) - 1];
        }

        if (stripos($current_url, $url_host) === false) {
            return false;
        }

        return true;
    }

    /**
     * 获取分页查询limit
     * @param $url
     * @param int $num
     * @return array
     */
    protected function pageLimit($url, $num = 10)
    {
        $url = str_replace(urlencode('{page}'), '{page}', $url);
        $page = isset($this->pager['obj']) && is_object($this->pager['obj']) ? $this->pager['obj'] : app(Page::class);
        $cur_page = $page->getCurPage($url);
        $limit_start = ($cur_page - 1) * $num;
        $limit = $limit_start . ',' . $num;
        $this->pager = [
            'obj' => $page,
            'url' => $url,
            'num' => $num,
            'cur_page' => $cur_page,
            'limit' => $limit
        ];
        list($start, $pernum) = explode(',', $limit);
        return ['start' => $start, 'limit' => $pernum];
    }

    /*
     * 分页结果显示
     */
    protected function pageShow($count)
    {
        return $this->pager['obj']->show($this->pager['url'], $count, $this->pager['num']);
    }

    /**
     * 上传文件（可上传到本地服务器或OSS）
     * @param string $savePath
     * @param bool $hasOne
     * @param string $upload_name 指定上传 name 值
     * @return array
     */
    protected function upload($savePath = '', $hasOne = false, $upload_name = null)
    {
        $files = request()->file($upload_name);
        $res = [];
        if ($files) {

            $config = cache('shop_config');
            if (is_null($config)) {
                $config = app(\App\Services\Common\ConfigService::class)->getConfig();
            }

            $disk = 'public';
            if (isset($config['open_oss']) && $config['open_oss'] == 1) {
                $cloud_storage = $config['cloud_storage'] ?? 0;
                if ($cloud_storage == 1) {
                    $disk = 'obs';
                } else {
                    $disk = 'oss';
                }
            }

            foreach ($files as $key => $file) {
                if ($file && $file->isValid()) {
                    $path[$key] = $file->storePublicly($savePath, $disk);
                    if ($path[$key]) {
                        // 上传成功 获取上传文件信息
                        $res[$key]['error'] = 0;
                        $res[$key]['url'] = Storage::disk($disk)->url($path[$key]);
                        $res[$key]['file_path'] = rtrim($savePath, '/') . '/' . $file->hashName(); // data/wewe123.jpg
                        $res[$key]['file_name'] = $file->hashName();
                        $res[$key]['size'] = $file->getSize();
                        $res[$key]['fileinfo'] = $file->getFileInfo(); //文件信息
                    } else {
                        // 上传错误提示
                        $res[$key]['error'] = $file->getError();
                        $res[$key]['message'] = $file->getErrorMessage();
                    }
                }
            }

            if ($res && $hasOne) {
                $res = reset($res);
            }
        }

        return $res;
    }

    /**
     * 删除文件（可删除本地服务器文件或OSS文件）
     * @param string $file 相对路径 data/attached/article/pOFEQJ3wSab1vhsrCVr5k6eU2m7e1bQ7W16dcc14.jpeg
     * @return bool
     */
    protected function remove($file = '')
    {
        if (empty($file) || in_array($file, ['/', '\\'])) {
            return false;
        }

        $config = cache('shop_config');
        if (is_null($config)) {
            $config = app(\App\Services\Common\ConfigService::class)->getConfig();
        }

        $disk = 'public';
        if (isset($config['open_oss']) && $config['open_oss'] == 1) {
            $cloud_storage = $config['cloud_storage'] ?? 0;
            if ($cloud_storage == 1) {
                $disk = 'obs';
            } else {
                $disk = 'oss';
            }
        }

        return Storage::disk($disk)->delete($file);
    }

    /**
     * 下载服务器文件到本地
     * @param string $file
     * @return bool
     */
    protected function file_download($file = '')
    {
        if (empty($file)) {
            return false;
        }

        $disk = 'public';
        $exists = Storage::disk($disk)->exists($file);
        if ($exists) {
            return Storage::disk($disk)->download($file);
        }
        return false;
    }

    /**
     * 附件镜像到阿里云OSS
     * @param string $file 文件相对路径 如 data/attend/1.jpg
     * @param bool $is_delete 是否要删除本地图片
     * @return string
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    protected function ossMirror($file = '', $is_delete = false)
    {
        $config = cache('shop_config');
        if (is_null($config)) {
            $config = app(\App\Services\Common\ConfigService::class)->getConfig();
        }

        if (isset($config['open_oss']) && $config['open_oss'] == 1) {
            $exists = Storage::disk('public')->exists($file);
            if ($exists) {
                $cloud_storage = $config['cloud_storage'] ?? 0;
                if ($cloud_storage == 1) {
                    $cloudDriver = 'obs';
                } else {
                    $cloudDriver = 'oss';
                }

                // oss 若存在则覆盖原文件
                $fileContents = Storage::disk('public')->get($file);
                Storage::disk($cloudDriver)->put($file, $fileContents);

                if ($is_delete == true) {
                    Storage::disk('public')->delete($file); // 删除本地
                }
            }
        }

        return $file;
    }

    /**
     * 同步上传服务器图片到OSS
     * @param array $filelist 图片列表 如 array('0'=>'data/attend/1.jpg', '1'=>'data/attend/2.png')
     * @param bool $is_delete 是否要删除本地图片
     * @return bool
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    protected function BatchUploadOss($filelist = [], $is_delete = false)
    {
        if (empty($filelist)) {
            return false;
        }

        $config = cache('shop_config');
        if (is_null($config)) {
            $config = app(\App\Services\Common\ConfigService::class)->getConfig();
        }

        // 开启OSS
        if (isset($config['open_oss']) && $config['open_oss'] == 1 && $filelist) {
            foreach ($filelist as $k => $file) {
                $image_name = $this->ossMirror($file);
                if ($is_delete == true) {
                    Storage::disk('public')->delete($file); // 删除本地
                }
            }
            return isset($image_name) ? true : false;
        }
    }

    /**
     * 同步下载OSS图片到本地服务器
     * @param array $filelist 图片列表 如 array('0'=>'data/attend/1.jpg', '1'=>'data/attend/2.png')
     * @return bool
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    protected function BatchDownloadOss($filelist = [])
    {
        if (empty($filelist)) {
            return false;
        }

        $config = cache('shop_config');
        if (is_null($config)) {
            $config = app(\App\Services\Common\ConfigService::class)->getConfig();
        }

        // 开启OSS
        if (isset($config['open_oss']) && $config['open_oss'] == 1 && $filelist) {
            $cloud_storage = $config['cloud_storage'] ?? 0;
            if ($cloud_storage == 1) {
                $cloudDriver = 'obs';
            } else {
                $cloudDriver = 'oss';
            }

            foreach ($filelist as $k => $file) {
                $exist_oss = Storage::disk($cloudDriver)->exists($file);
                if ($exist_oss) {
                    $fileContents = Storage::disk($cloudDriver)->get($file);

                    Storage::disk('public')->put($file, $fileContents);
                }
            }
            return true;
        }
    }
}
