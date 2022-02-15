<?php
namespace app\modules\crud\helpers;

use yii\helpers\Inflector;

/**
 * Description of Class
 *
 */
class ClassI18N
{
    public static function class2messagesPath($class)
    {
        $pos = strrpos($class, '\\');
        if (false != $pos) {
            $class = substr($class, 0, $pos) . '\\' . Inflector::camel2id(lcfirst(substr($class, $pos + 1)));
        }

        return strtolower($class);
    }
}
