<?php

namespace core_download;
class LoadFiles
{
    static function init()
    {
        $core_dir = Config::$plugin_dir . '/core';
        require_once "$core_dir/Plugin.php";
        require_once "$core_dir/WordPress.php";
        require_once "$core_dir/Options.php";
        require_once "$core_dir/Template.php";
        require_once "$core_dir/Tools.php";

        require_once "$core_dir/Ajax.php";
        global $core_download_set;
        $core_download_set = Options::getAdminSet();

        Plugin::init();
        Ajax::init();
        add_action('admin_enqueue_scripts', [static::class, '_loadFileOnAdmin']);
        add_action('wp_enqueue_scripts', array(static::class, '_LoadFileOnSite'));

    }

    static function _LoadFileOnSite()
    {
        if (is_single()) {
            WordPress::loadCss('core_download_front', 'front.css');
            WordPress::loadJS('core_download_home', 'home.min.js', true, [], true);

        }
    }

    static function _loadFileOnAdmin($hook)
    {
        WordPress::loadCss('core-download-admin', 'admin.css');
        wp_localize_script('jquery', 'core_download', Options::getAdminSet($hook));
        if ($hook == 'post.php' || $hook == 'post-new.php') {
            WordPress::loadJS('core_download_editor', 'editor.min.js', true, [], true);
        }
        if ($hook == 'toplevel_page_core_download_set') {
            global $core_download_set;
            WordPress::echoJson('core_download_set',$core_download_set);
            WordPress::loadCss('core-download-admin-set', 'admin-set.css');
            WordPress::loadJS('core_download_admin_set', 'admin-set.min.js', true, [], true);
        }
    }
}