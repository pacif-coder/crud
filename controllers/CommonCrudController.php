<?php
namespace app\modules\crud\controllers;

use Yii;

/**
 *
 */
abstract class CommonCrudController extends CrudController
{
    protected static $modelName2modelClass = [];

    public function init()
    {
        $name = Yii::$app->request->get('model-name');
        if ($name && isset(static::$modelName2modelClass[$name])) {
            $this->modelClass = static::$modelName2modelClass[$name];
        }

        parent::init();
    }
}