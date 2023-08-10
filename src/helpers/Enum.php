<?php
namespace Crud\helpers;

use yii\db\ActiveQueryInterface;
use yii\helpers\ArrayHelper;
use yii\validators\ExistValidator;

use Crud\helpers\ModelName;
use Crud\models\ModelWithOrderInterface;

use Exception;

/**
 *
 *
 */
class Enum
{
    protected static $activeQueries = [];

    protected static $cash = [];

    protected static $existValidatorts;

    public static function isEnum($model, $attr)
    {
        $class = get_class($model);
        if (isset(self::$activeQueries[$class][$attr])) {
            return true;
        }

        $method = "get{$attr}";
        if ($model->hasMethod($method)) {
            $query = $model->{$method}();
            if ($query instanceof ActiveQueryInterface) {
                self::$activeQueries[$class][$attr] = $query;
                return true;
            }
        }

        self::initValidators($model);
        if (isset(self::$existValidatorts[$class][$attr])) {
            return true;
        }

        return false;
    }

    protected static function initValidators($model)
    {
        if (null !== self::$existValidatorts) {
            return;
        }

        $class = get_class($model);
        self::$existValidatorts = [];
        foreach ($model->getActiveValidators() as $validator) {
            /*@var $validator \yii\validators\Validator */
            foreach ($validator->getAttributeNames() as $attr) {
                if ($validator instanceof ExistValidator) {
                    self::$existValidatorts[$class][$attr] = $validator;
                }
            }
        }

    }

    public static function getList($model, $attr)
    {
        self::initValidators($model);

        $class = get_class($model);
        if (isset(self::$existValidatorts[$class][$attr])) {
            /* @var $validator ExistValidator */
            $validator = self::$existValidatorts[$class][$attr];
            $targetModelClass = $validator->targetClass;
            $targetModelAttr = $validator->targetAttribute[$attr];

            $options = [];
            $nameAttr = ModelName::getNameAttr($targetModelClass);
            if (!$nameAttr) {
                throw new Exception("It is not possible to define a name attribute in class '{$targetModelClass}'");
            }

            foreach ($targetModelClass::find()->orderBy($nameAttr)->all() as $targetModel) {
                $options[$targetModel->{$targetModelAttr}] = $targetModel->{$nameAttr};
            }

            return $options;
        }

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
        self::initValidators($model);
        $modelClass = get_class($model);

        if (isset(self::$existValidatorts[$modelClass][$attr])) {
            return false;
        }

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