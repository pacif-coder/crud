<?php
namespace app\modules\crud\helpers;

/**
 *
 *
 */
class PhpHelper
{
    public static function data2code($array, $oneLine = true, $intend = '')
    {
        if (!is_array($array) && !is_object($array)) {
            return var_export($array, true);
        }

        $withKey = false;
        foreach (array_keys($array) as $i => $key) {
            if (!is_int($key) || $key != $i) {
                $withKey = true;
                break;
            }
        }

        $pad = $intend . str_repeat(' ', 4);

        if ($oneLine) {
            $str = '[';
        } else {
            $str = "{$intend}[\n";
        }

        foreach ($array as $key => $value) {
            if (!$oneLine) {
                $str .= $pad;
            }

            if ($withKey) {
                $str .= var_export($key, true) . ' => ';
                $str .= ltrim(self::data2code($value, $oneLine, $pad));
            } else {
                $str .= self::data2code($value, $oneLine, $pad);
            }

            $str .= ',';
            $str .= $oneLine? ' ' : "\n";
        }

        if ($oneLine) {
            $str = substr($str, 0, -2);
            $str .= ']';
        } else {
            $str .= "{$intend}]";
        }

        return $str;
    }

    public static function tab($count = 1)
    {
        return str_repeat(' ', $count * 4);
    }
}