<?php
namespace app\modules\crud\grid\column;

use yii\helpers\Url;

/**
 *
 */
class ActionColumn extends \yii\grid\ActionColumn
{
    public $action2controller = [];

    public function createUrl($action, $model, $key, $index)
    {
        if (is_callable($this->urlCreator)) {
            return call_user_func($this->urlCreator, $action, $model, $key, $index, $this);
        }

        $params = is_array($key) ? $key : ['id' => (string) $key];

        $controller = $this->getController($action);
        $params[0] =  $controller ? $controller . '/' . $action : $action;

        return Url::toRoute($params);
    }

    public function getController($action)
    {
        if (isset($this->action2controller[$action])) {
            return $this->action2controller[$action];
        }

        return $this->controller;
    }
}
