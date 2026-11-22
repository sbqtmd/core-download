<?php

namespace core_download;
class Config
{
    static $plugin_dir;
    static $plugin_url;
    static $plugin_name;
    static $plugin_version;
    static $plugin_version_name;
    static $static_url;
    static $js_url;
    static $css_url;
    static $img_url;
    static $lib_url;
    static $set_name;
    static $plugin_server_url;
    static $is_development;
    static $img_dir;
    static function init()
    {
        self::$plugin_name = 'core_download';
        self::$plugin_server_url = 'https://v.lovestu.com/getNewestVersion?key=coredownload';
        //self::$plugin_server_url = 'http://v.wpwp.com/core-download/version.json';

        self::$plugin_dir = WP_PLUGIN_DIR . '/core-download';
        self::$plugin_url = WP_PLUGIN_URL . '/core-download';
        if (is_ssl()) {
            self::$plugin_url = str_replace('http://', 'https://', self::$plugin_url);
        }
        self::$static_url = self::$plugin_url . '/static';
        self::$js_url = self::$static_url . '/js';
        self::$css_url = self::$static_url . '/css';
        self::$lib_url = self::$static_url . '/lib';
        self::$img_url = self::$static_url . '/img';
        self::$img_dir = self::$plugin_dir . '/static/img';

        self::$plugin_version = 6;
        self::$plugin_version_name = '1.0.6';
        self::$set_name = 'core_download_set';
        self::$is_development = false;
        require_once 'LoadFiles.php';
        LoadFiles::init();
    }
}

Config::init();