<?php
namespace app\modules\crud\helpers;

use yii\db\ActiveQueryInterface;
use yii\helpers\ArrayHelper;

use app\modules\crud\helpers\ModelName;
use app\modules\crud\models\ModelWithOrderInterface;

use Exception;

/**
 *
 *
 */
class Enum
{
    protected static $activeQueries = [];

    protected static $cash = [];

    public static function isEnum($model, $attr)
    {
        $class = get_class($model);
        if (isset(self::$activeQueries[$class][$attr])) {
            return true;
        }

        $method = "get{$attr}";
        if (!$model->hasMethod($method)) {
            return false;
        }

        $query = $model->{$method}();
        if (!($query instanceof ActiveQueryInterface)) {
            return false;
        }

        return true;
    }

    public static function getList($model, $attr)
    {
        $query = self::getQuery($model, $attr);
        $class = $query->modelClass;

        $keys = $class::primaryKey();
        if (count($keys) > 1) {
            throw new Exception('Not support');
        }

        $nameAttr = ModelName::getNameAttr($class);
        if (!$nameAttr) {
            throw new Exception("Model '{$class}' mast have 'name' attr");
        }

        $query->asArray();

        if (is_a($class, ModelWithOrderInterface::class, true)) {
            $query->orderBy($class::ORDER_ATTR);
        } else {
            $query->orderBy($nameAttr);
        }

        return ArrayHelper::map($query->all(), current($keys), $nameAttr);
    }

    public static function isMultiple($model, $attr)
    {
        return self::getQuery($model, $attr)->multiple;
    }

    public static function getQuery($model, $attr)
    {
        $class = get_class($model);
        if (isset(self::$activeQueries[$class][$attr])) {
            return self::$activeQueries[$class][$attr];
        }

        $method = "get{$attr}";
        if (!$model->hasMethod($method)) {
            throw new Exception("Method {$method} is not exist");
        }

        $query = $model->{$method}();
        if (!($query instanceof ActiveQueryInterface)) {
            throw new Exception("Query is not ActiveQueryInterface ");
        }

        $query->via = null;
        $query->primaryModel = null;

        return self::$activeQueries[$class][$attr] = $query;
    }

    public static function cashExists($key)
    {
        return isset(self::$cash[$key]);
    }

    public static function cashGet($key)
    {
        return isset(self::$cash[$key])? self::$cash[$key] : false;
    }

    public static function cashSet($key, $value)
    {
        return self::$cash[$key] = $value;
    }
}