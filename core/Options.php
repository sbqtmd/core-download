<?php

namespace core_download;
use mysql_xdevapi\Exception;

class Options
{
    static function getAdminSet()
    {

        $data['img_url'] = Config::$img_url;
        $data['ajax_url'] = admin_url('admin-ajax.php');
        $data['ajax_name'] = Config::$plugin_name;
        $data['version_name'] = Config::$plugin_version_name;
        $data['version'] = Config::$plugin_version;

        $data['set'] = self::getOptions();
        return $data;
    }

    static function saveSet($data)
    {
        return update_option(Config::$set_name, base64_encode(json_encode($data)));
    }

    static function getOptions()
    {
        $default = self::getDefaultOptions();
        $set = get_option(Config::$set_name, false);
        if ($set === false) {
            return $default;
        }
        try {
            if (is_string($set)) {
                $set_obj = json_decode(base64_decode($set), true);
                if ($set_obj === false) {
                    return $default;
                } else {
                    return self::updateOptions($set_obj, $default);
                }
            }else{
                return $default;
            }
        }catch(\Exception $e){
            return $default;
        }
    }

    private static function getDefaultOptions()
    {
        $data['need_update'] = false;
        $data['open'] = false;
        $data['last_update'] = time();
        $data['complaint_url'] = '';
        $data['theme_color'] = '#409EFF';
        $data['border_radius'] = '8';
        $data['copyright_str'] = '本站所有资源均来源于网络，仅供学习使用，请下载后24小时内删除';
        return $data;
    }

    private static function updateOptions($set, $default_set)
    {
        foreach ($default_set as $key => &$item) {
            if (isset($set[$key])) {
                $item = $set[$key];
            }
        }
        return $default_set;
    }

    static function getMetaBoxDefaultOptions()
    {
        $data['open'] = true;
        $data['content'] = '';
        $data['download_list'] = [];
        return $data;
    }

    static function getMetaBoxOptions($post_id)
    {
        $meta_box = get_post_meta($post_id, 'core_download_meta_box', true);
        if (empty($meta_box)) {
            return self::getMetaBoxDefaultOptions();
        }
        $json = json_decode(base64_decode($meta_box), true);
        if ($json) {
            $json = self::updateOptions($json, self::getMetaBoxDefaultOptions());
            foreach ($json['download_list'] as &$item) {
                $item = self::updateOptions($item, self::getMetaBoxDownLoadUrlDefaultOptions());
            }
            return $json;
        }
        return self::getMetaBoxDefaultOptions();
    }

    static function getMetaBoxDownLoadUrlDefaultOptions()
    {
        $data['name'] = 'local';
        $data['key'] = '';
        $data['url'] = '';
        $data['resource_name'] = '';
        $data['show_type'] = '';
        return $data;
    }

    static function saveMetaBoxData($post_id, $data)
    {
        update_post_meta($post_id, 'core_download_meta_box', $data);
    }

}