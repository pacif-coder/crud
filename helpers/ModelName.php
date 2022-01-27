<?php
namespace app\modules\crud\helpers;

use app\modules\crud\models\ModelWithNameAttrInterface;

/**
 *
 */
class ModelName
{
    protected static $class2nameAttr = [];

    public static function getName($model)
    {
        $nameAttr = self::getNameAttr($model);
        if ($nameAttr && is_object($model)) {
            return $model->{$nameAttr};
        }
    }

    public static function getNameByClass($class, $objectData)
    {
        $nameAttr = self::getNameAttr($class);
        if (!$nameAttr) {
            return;
        }

        if (is_array($objectData)) {
            return $objectData[$nameAttr];
        }
    }

    public static function getNameAttr($modelClass)
    {
        if (is_object($modelClass)) {
            $modelClass = get_class($modelClass);
        }

        if (array_key_exists($modelClass, self::$class2nameAttr)) {
            return self::$class2nameAttr[$modelClass];
        }

        if (is_a($modelClass, ModelWithNameAttrInterface::class, true)) {
            self::$class2nameAttr[$modelClass] = $modelClass::NAME_ATTR;
        } else {
            self::$class2nameAttr[$modelClass] = null;
        }

        return self::$class2nameAttr[$modelClass];
    }
}