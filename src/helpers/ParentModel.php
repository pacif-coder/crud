<?php
namespace Crud\helpers;

use yii\base\Model;
use yii\validators\ExistValidator;

use Crud\models\ModelWithParentInterface;
use Crud\helpers\ModelName;

use Exception;

/**
 *
 */
class ParentModel
{
    protected static $parents = [];

    protected static $class2parentClass = [];
    protected static $class2parentClassToAttr = [];
    protected static $class2parentModelAttr = [];

    public static function loadParents($model)
    {
        $modelClass = get_class($model);
        $attr = self::getParentModelAttr($model);
        if (!$attr) {
            return [];
        }

        $modelParentClass = self::getParentModelClass($model);
        if (!$modelParentClass) {
            return [];
        }

        $modelParentID = $model->{$attr};
        if (isset(self::$parents[$modelParentID])) {
            return self::$parents[$modelParentID];
        }

        $list = [];
        $i = 0;
        while (true) {
            $parentClass = self::getParentModelClass($model);
            if (!$parentClass) {
                break;
            }

            // get parent model ID
            $attr = self::getParentModelAttr($model);
            if (!$attr) {
                break;
            }

            $parentID = $model->{$attr};
            if (!$parentID) {
                break;
            }

            // get parent model primary key
            $modelClass = get_class($model);
            $parentIDAttr = self::$class2parentClassToAttr[$modelClass];

            unset($model);
            $model = $parentClass::findOne([$parentIDAttr => $parentID]);
            if (!$model) {
                break;
            }

            $list[$i] = [
                'id' => $model->{$parentIDAttr},
                'name' => ModelName::getName($model),
                'class' => $parentClass,
                'model' => $model,
            ];

            $i++;
        }

        return self::$parents[$modelParentClass][$modelParentID] = array_reverse($list);
    }

    public static function getParentID(Model $model)
    {
        $attr = self::getParentModelAttr($model);
        if (!$attr) {
            throw new Exception("Not have parent attr in class '{$class}'");
        }

        if (!$model->hasProperty($attr)) {
            $class = get_class($model);
            throw new Exception("Not have attr '{$attr}' in class '{$class}'");
        }

        return $model->{$attr};
    }

    public static function getParentModelAttr($modelClass)
    {
        if (is_object($modelClass)) {
            $modelClass = get_class($modelClass);
        }

        if (array_key_exists($modelClass, self::$class2parentModelAttr)) {
            return self::$class2parentModelAttr[$modelClass];
        }

        if (!is_subclass_of($modelClass, ModelWithParentInterface::class)) {
            return self::$class2parentModelAttr[$modelClass] = null;
        }

        if (!defined("$modelClass::PARENT_MODEL_ATTR")) {
            throw new Exception("Not find parent attr in class '{$modelClass}'");
        }

        return self::$class2parentModelAttr[$modelClass] = $modelClass::PARENT_MODEL_ATTR;;
    }

    public static function getParentModelClass($model)
    {
        if (is_object($model)) {
            $modelClass = get_class($model);
        } else {
            $modelClass = $model;
        }

        if (array_key_exists($modelClass, self::$class2parentClass)) {
            return self::$class2parentClass[$modelClass];
        }

        $attr = self::getParentModelAttr($modelClass);
        if (!$attr) {
            return self::$class2parentClass[$modelClass] = null;
        }

        $targetClass = $targetClassToAttr = null;
        foreach ($model->getActiveValidators() as $validator) {
            /* @var $validator \yii\validators\Validator */
            if (!is_a($validator, ExistValidator::class)) {
                continue;
            }

            if (1 != count($validator->attributes) || !in_array($attr, $validator->attributes)) {
                continue;
            }

            $targetClass = $validator->targetClass;
            $targetClassToAttr = $validator->targetAttribute[$attr];
            break;
        }

        self::$class2parentClassToAttr[$modelClass] = $targetClassToAttr;
        return self::$class2parentClass[$modelClass] = $targetClass;
    }
}
