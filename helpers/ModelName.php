<?php
namespace Crud\helpers;

use Yii;
use yii\db\ActiveRecord;

use Crud\models\ModelWithNameAttrInterface;

use ReflectionClass;
use ReflectionProperty;

/**
 *
 */
class ModelName
{
    protected static $class2nameAttr = [];

    protected static $uptake = true;

    protected static $nameAttrs = ['name'];

    protected static $isInit;

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

        self::_init();
        return self::$class2nameAttr[$modelClass] = self::_getNameAttr($modelClass);
    }

    protected static function _init()
    {
        if (self::$isInit) {
            return;
        }

        $params = Yii::$app->params;
        if (!isset($params['modelName'])) {
            return;
        }

        foreach ($params['modelName'] as $key => $value) {
            self::$$key = $value;
        }
    }

    public static function _getNameAttr($modelClass)
    {
        if (is_a($modelClass, ModelWithNameAttrInterface::class, true)) {
            return $modelClass::NAME_ATTR;
        }

        if (!self::$uptake) {
            return;
        }

        $ref = new ReflectionClass($modelClass);
        foreach ($ref->getProperties(ReflectionProperty::IS_PUBLIC) as $prop) {
            $name = $prop->getName();
            if (in_array($name, self::$nameAttrs)) {
                return $name;
            }
        }

        if (is_a($modelClass, ActiveRecord::class, true)) {
            $columns = array_keys($modelClass::getTableSchema()->columns);
            foreach ($columns as $column) {
                if (in_array($column, self::$nameAttrs)) {
                    return $column;
                }
            }
        }
    }
}