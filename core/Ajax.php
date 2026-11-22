<?php

namespace core_download;

class Ajax
{
    static function init()
    {
        add_action('wp_ajax_' . Config::$plugin_name, array(static::class, 'ajax'));
        add_action('wp_ajax_nopriv_' . Config::$plugin_name, array(static::class, 'ajax'));
    }

    static function ajax()
    {
        $fun_name = $_POST['fun'];
        if (!isset($fun_name)) {
            $fun_name = $_GET['fun'];
            if (!isset($fun_name)) {
                $fun_name = 'error';
            }
        }
        call_user_func(array(static::class, $fun_name));
    }

    static private function setAjaxDataAndDie($code, $msg = '', $data = null)
    {
        $_data['code'] = $code;
        $_data['msg'] = $msg;
        if ($data != null) {
            $_data['data'] = $data;
        }
        wp_die(json_encode($_data, JSON_UNESCAPED_UNICODE));
    }

    static private function validationParameters($arr)
    {
        $parameter = [];
        foreach ($arr as $item) {
            if ($item[1] == 1) {
                if (!isset($_POST[$item[0]])) {
                    self::setAjaxDataAndDie(500, '参数错误');
                }
                $parameter[$item[0]] = $_POST[$item[0]];
            } else {
                if (!isset($_GET[$item[0]])) {
                    self::setAjaxDataAndDie(500, '参数错误');
                }
                $parameter[$_GET[0]] = $_GET[$item[0]];
            }
        }

        return $parameter;
    }

    static private function needLogin($user_type = 'user')
    {
        if (!is_user_logged_in()) {
            self::setAjaxDataAndDie(500, '无权限访问');
        }
        if ($user_type == 'admin') {
            if (!WordPress::isAdmin()) {
                self::setAjaxDataAndDie(500, '权限不足');
            }
        }
    }

    static function saveSet()
    {
        self::needLogin('admin');
        $arr = [['data', 1]];
        $arr = self::validationParameters($arr);
        $re = Options::saveSet(json_decode(base64_decode($arr['data'], true)));
        $code = 500;
        if ($re) {
            $code = 200;
        }
        self::setAjaxDataAndDie($code);
    }

    static function checkUpdate()
    {
        global $core_download_set;
        $time = time();
        // print_r($time - $core_download_set['set']['last_update']);
        /*if ($core_download_set['set']['need_update']) {
            self::setAjaxDataAndDie(200, 'need update');
        }*/
        $type = 'admin';
        if (isset($_POST['type'])) {
            $type = 'plugin';
        }
        if ($time - $core_download_set['set']['last_update'] > 3600 || $type === 'plugin') {
            $plugin_info = Plugin::getPluginInfo();
            if ($plugin_info === false) {
                self::setAjaxDataAndDie(500, '获取失败');
            }
            $core_download_set['set']['last_update'] = time();
            if ($plugin_info['dev_version'] > Config::$plugin_version) {
                $core_download_set['set']['need_update'] = true;
                Options::saveSet($core_download_set['set']);
            } else {
                $core_download_set['set']['need_update'] = false;
                Options::saveSet($core_download_set['set']);
            }
            self::setAjaxDataAndDie(200, 'success', $plugin_info);
        } else {
            self::setAjaxDataAndDie(200, 'time not');
        }
    }

    static function update()
    {
        /*$dir = WP_PLUGIN_DIR . '/wpopt_tmp';
           var_dump(scandir($dir));
           self::setAjaxDataAndDie(500);*/
        if (Config::$is_development) {
            self::setAjaxDataAndDie(500, '当前为开发模式');
        }
        $plugin_info = Plugin::getPluginInfo();
        if ($plugin_info !== false) {
            if ($plugin_info['dev_version'] > Config::$plugin_version) {
                //准备更新
                if (isset($plugin_info['all_package_url'])) {
                    $url = $plugin_info['all_package_url'];
                    $dir = WP_PLUGIN_DIR . '/core_download_tmp';
                    if (!is_dir($dir)) {
                        mkdir($dir);
                    }
                    $remote_file = fopen($url, 'r');
                    $file_name = time() . '.zip';
                    $fh = fopen($dir . "/{$file_name}", 'w');
                    while (!feof($remote_file)) {
                        $output = fread($remote_file, 8192);
                        fwrite($fh, $output);
                    }
                    fclose($remote_file);
                    $file_path = $dir . '/' . $file_name;
                    if (strtoupper(md5_file($file_path)) != $plugin_info['all_package_url_md5']) {
                        Tools::delDir($dir);
                        self::setAjaxDataAndDie(500, '更新失败，文件校验失败，请手动更新');
                    }
                    $zip = new \ZipArchive();
                    //self::setAjaxDataAndDie(200, '更新成功，请刷新！');
                    if ($zip->open($file_path) === true) {
                        $zip->extractTo(WP_PLUGIN_DIR);
                        $zip->close();
                    } else {
                        Tools::delDir($dir);
                        self::setAjaxDataAndDie(500, '解压失败，可能没有权限');
                    }
                    global $core_download_set;
                    Tools::delDir($dir);
                    $core_download_set = Options::getOptions();
                    $core_download_set['need_update'] = false;
                    Options::saveSet($core_download_set);
                    self::setAjaxDataAndDie(200, '更新成功，请刷新！');
                } else {
                    self::setAjaxDataAndDie(500, '更新失败，未获取到下载地址');
                }
            } else {
                self::setAjaxDataAndDie(500, '未发现新版本');
            }
        }

    }
}