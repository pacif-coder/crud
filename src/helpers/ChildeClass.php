<?php
namespace Crud\helpers;

use Yii;
use Crud\models\tree_node\ActiveRecord;

use Exception;

/**
 *
 *
 */
class ChildeClass
{
    protected static $class2ChildeClasses = [];

    public static function getChildeClass($modelClass, $modelData = null)
    {
        if (is_object($modelClass)) {
            $modelData = $modelClass;
            $modelClass = get_class($modelClass);
        }

        if (isset(self::$class2ChildeClasses[$modelClass])) {
            return self::$class2ChildeClasses[$modelClass];
        }

        if (!is_a($modelClass, ActiveRecord::class, true)) {
            throw new Exception('Unsupport not ActiveRecord class');
        }

        $class = $modelClass::CHILD_CLASS;
        if ($class) {
            self::$class2ChildeClasses[$modelClass] = $class;
            return $class;
        }

        if (is_object($modelData)) {
            return $modelData->getChildClass();
        }

        /* @var $model ActiveRecord */
        $model = Yii::createObject($modelClass);
        $modelClass::populateRecord($model, $modelData);
        return $model->getChildClass();
    }
}