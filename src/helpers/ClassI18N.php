<?php
namespace Crud\helpers;

use yii\helpers\Inflector;

/**
 * Description of Class
 *
 */
class ClassI18N
{
    public static function class2messagesCategory($class)
    {
        return static::class2messagesPath($class);
    }

    public static function class2messagesPath($class)
    {
        $pos = strrpos($class, '\\');
        if (false != $pos) {
            $part1 = substr($class, 0, $pos);
            $part2 = substr($class, $pos + 1);

            if (strtoupper($part2) != $part2) {
                $part2 = Inflector::camel2id(lcfirst($part2));
            }

            $class = $part1 . '\\' . $part2;
        }

        return strtolower($class);
    }
}
