<?php

namespace core_download;
class Plugin
{
    static function init()
    {
        add_action('admin_menu', [static::class, 'regMenu']);
        add_action('add_meta_boxes', [static::class, 'addMetaBox']);
        add_action('save_post', [static::class, 'saveMetaBox']);
        add_filter('the_content', [static::class, 'addDownloadWrap']);
        add_action('wp_footer', [static::class, 'handleFooter']);
        add_action('admin_footer', [static::class, 'adminFooter']);
    }

    static function handleFooter()
    {
        global $core_download_set;
        print_r("<style>.core-download-warp {--theme-color: " . $core_download_set['set']['theme_color'] . ";--border-radius:" . $core_download_set['set']['border_radius'] . "px;}</style>");
    }

    static function addDownloadWrap($content)
    {
        $post_id = get_the_ID();
        $download_data = Options::getMetaBoxOptions($post_id);
        global $core_download_set;
        if (!$download_data['open'] || count($download_data['download_list']) == 0 || $core_download_set['set']['open'] === false) {
            return $content;
        }
        $list = $download_data['download_list'];
        $download_data_html = '';
        foreach ($list as $item) {
            $item['icon'] = file_get_contents(Config::$img_dir . "/icon/{$item['name']}.svg");
            $item['copy_icon'] = Config::$img_url . "/copy.svg";
            $item['netdisk_name'] = self::netdiskNameToWord($item['name']);
            if ($item['resource_name'] == '') {
                $item['hide_netdisk_name'] = 'true';
            }
            if ($item['key'] == '') {
                $item['hide_key'] = 'true';
                $item['key'] = '无';
            }
            $download_data_html .= Template::getTemplateHtmlByDOMDocument('download-url-item', $item);
        }
        $data['download_data'] = $download_data_html;
        $data['title'] = get_the_title();
        $data['open_link'] = file_get_contents(Config::$img_dir . "/open-link.svg");
        $data['content'] = stripslashes(base64_decode($download_data['content']));
        $data['copyright_str'] = $core_download_set['set']['copyright_str'];
        $data['front_logo'] = file_get_contents(Config::$img_dir . "/front_logo.svg");
        $data['warn_icon'] = file_get_contents(Config::$img_dir . "/warn_icon.svg");
        $data['complaint_url'] = $core_download_set['set']['complaint_url'];
        $content .= Template::getTemplateHtml('download-warp', $data);
        return $content;
    }

    static function netdiskNameToWord($name)
    {
        $cloud_list = [
            'local' => '本地下载',
            'aliyun' => '阿里云盘',
            'baidu' => '百度云盘',
            '360' => '360网盘',
            'ct' => '诚通网盘',
            'lanzou' => '蓝奏云',
            '123' => '123盘',
            'github' => 'github',
            'quark' => '夸克',
            'uc' => 'UC',
            'weiyun' => '微云',
            'wenshushu' => '文叔叔',
            'xunlei' => '迅雷',
            'jianguo' => '坚果云',
            'tianyi' => '天翼云',
            'hecai' => '和彩云',
            'onedrive' => 'OneDrive'
        ];
        return $cloud_list[$name];
    }

    static function regMenu()
    {
        global $core_download_set;

        if ($core_download_set['set']['need_update'] === true) {

            $menu_html = '<span class="awaiting-mod">new</span>';
        } else {
            $menu_html = '';
        }
        add_menu_page('下载增强 CoreDownload', '下载增强' . $menu_html, 'administrator', 'core_download_set', function () {
            require_once Config::$plugin_dir . '/pages/admin-set.php';
        }, 'dashicons-icon-core-download');
    }

    static function addMetaBox()
    {
        add_meta_box(
            'core_download_meta_box',
            '<div class="core_download_meta_box-title">Core Download 下载增强</div>',
            [static::class, 'renderMetaBox'], // 渲染Meta Box的回调函数
            'post',
            'normal',
            'high'
        );
    }

    static function renderMetaBox()
    {
        $data = Options::getMetaBoxOptions(get_the_ID());
        WordPress::echoJson('core_download_mete_box', $data);
        WordPress::echoJson('core_download_download_url_default', Options::getMetaBoxDownLoadUrlDefaultOptions());

        $editor_settings = array(
            'media_buttons' => false,
            'teeny' => false,
            'tinymce' => array(
                'toolbar1' => 'formatselect,bold,underline,blockquote,forecolor,alignleft,aligncenter,alignright,link,unlink,bullist,numlist,,undo,redo,fullscreen,wp_help',
                'toolbar2' => '',
                'height' => 100,
            ),
        );
        wp_editor(stripslashes(base64_decode($data['content'])), 'core_download_content', $editor_settings);
        ?>

        <div id="core-download-meta-box"></div>
        <?php
    }

    static function saveMetaBox($post_id)
    {
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        if (isset($_POST['core_download_meta_box']) && isset($_POST['core_download_content'])) {
            $data = json_decode(stripslashes($_POST['core_download_meta_box']), true);
            $data['content'] = base64_encode($_POST['core_download_content']);
            // file_put_contents(Config::$plugin_dir . "/log1.txt", stripslashes(base64_decode($data['content'])));
            Options::saveMetaBoxData($post_id, base64_encode(json_encode($data)));

        }
    }

    static function adminFooter()
    {
        $data['ajax_url'] = admin_url('admin-ajax.php');
        $data['ajax_name'] = Config::$plugin_name;
        print_r("<script>jQuery.post('" . $data['ajax_url'] . "',{action:'" . $data['ajax_name'] . "',fun:'checkUpdate'})</script>");
        ?>

        <?php
    }

    static function getPluginInfo()
    {
        $url = Config::$plugin_server_url . "&url=" . $_SERVER['HTTP_HOST'];
        $result = wp_remote_get($url);
        if (!is_wp_error($result)) {
            $json = json_decode($result['body'], true);
            if ($json['code'] == 200) {
                $data['version'] = $json['data']['version'];
                $data['dev_version'] = $json['data']['dev_version'];
                $data['all_package_url'] = $json['data']['all_package_url'];
                $data['all_package_url_md5'] = $json['data']['all_package_url_md5'];
                return $data;
            }
        } else {
            return false;
        }
        return false;
    }
}