<?php
namespace Crud\components;

/**
 *
 */
class ClassToController extends \yii\base\BaseObject
{
    public $map = [];

    public function getController($modelClass)
    {
        foreach ($this->map as $tmpModelClass => $controller) {
            if ($modelClass == $tmpModelClass) {
                return $controller;
            }
        }
    }
}
