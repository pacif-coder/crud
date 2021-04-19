<?php
namespace app\modules\crud\helpers;

use app\modules\crud\models\ModelWithNameAttrInterface;

use ReflectionClass;

/**
 *
 */
class ModelName
{
    public static $nameAttrs = ['name', 'title', 'fio', 'id'];

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

        self::$class2nameAttr[$modelClass] = self::_getNameAttr($modelClass);
        return self::$class2nameAttr[$modelClass];
    }

    protected static function _getNameAttr($modelClass)
    {
        if (is_subclass_of($modelClass, ModelWithNameAttrInterface::class)) {
            return $modelClass::NAME_ATTR;
        }

        $ref = new ReflectionClass($modelClass);
        $staticAttrs = $ref->getStaticProperties();
        if (isset($staticAttrs['fb_nameAttr'])) {
            return $staticAttrs['fb_nameAttr'];
        }

        $uptake = isset($staticAttrs['fb_uptake'])? $staticAttrs['fb_uptake'] : true;
        if (!$uptake) {
            return;
        }

        if (isset($staticAttrs['fb_fields'])) {
            $fields = $staticAttrs['fb_fields'];
            $names = array_intersect(self::$nameAttrs, $fields);
            if ($names) {
                return reset($names);
            }
        }

        if (isset($staticAttrs['fb_columns'])) {
            $fields = self::getAttrsByColumns($staticAttrs['fb_columns']);
            $names = array_intersect(self::$nameAttrs, $fields);
            if ($names) {
                return reset($names);
            }
        }

        $names = array_intersect(self::$nameAttrs, self::getAttrs($modelClass));
        if ($names) {
            return reset($names);
        }
    }

    protected static function getAttrs($modelClass)
    {
        $keys = $modelClass::primaryKey();
        $attrs = array_keys($modelClass::getTableSchema()->columns);
        return array_diff($attrs, $keys);
    }

    protected static function getAttrsByColumns($columns)
    {
        $matches = [];
        $reg = '/^([^:]+)(:(\w*))?(:(.*))?$/';

        $attrs = [];
        foreach ($columns as $column => $desc) {
            if (is_string($desc) && preg_match($reg, $desc, $matches)) {
                $attrs[] = $matches[1];
            } elseif (!isset($desc['attribute']) && !is_int($column)) {
                $attrs[] = $column;
            }
        }

        return $attrs;
    }
}