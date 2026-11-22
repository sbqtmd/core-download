<?php

namespace core_download;

class Tools
{
    static function delDir($dir)
    {
        $files = array_diff(scandir($dir), array('.', '..'));
        foreach ($files as $file) {
            if (is_dir("$dir/$file")) {
                $del_re = self::delDir("$dir/$file");
            } else {
                $del_re = unlink("$dir/$file");
            }
        }

        return rmdir($dir);
    }

    static function canConvertWebP()
    {
        if (extension_loaded('imagick') || function_exists('imagewebp')) {
            return true;
        }
        return false;
    }

    static function writeLog($content)
    {
        if (Config::$is_development) {
            $file = Config::$plugin_dir . "/log/log.txt";
            $content = file_get_contents($file) . "\n" . $content;
            file_put_contents($file, $content);
        }
    }
}