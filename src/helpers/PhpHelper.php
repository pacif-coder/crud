<?php
namespace Crud\helpers;

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

        $i = 0;
        foreach ($array as $key => $value) {
            if (!$oneLine) {
                $str .= $pad;
            }

            if ($withKey && $i !== $key) {
                $str .= var_export($key, true) . ' => ';
            }

            $str .= ltrim(self::data2code($value, $oneLine, $pad, 1));

            $str .= ',';
            $str .= $oneLine? ' ' : "\n";

            $i += 1;
        }

        if ($oneLine) {
            if (strlen($str) > 2) {
                $str = substr($str, 0, -2);
            }

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