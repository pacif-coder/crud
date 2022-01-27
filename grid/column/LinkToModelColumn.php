<?php
namespace app\modules\crud\grid\column;

use Yii;
use yii\base\InvalidConfigException;

/**
 * Create link to child model controller 
 */
class LinkToModelColumn extends ActionLinkColumn
{
    public $action = '';

    public $modelClass;

    public $backUrl = false;

    public function init()
    {
        parent::init();

        if (!$this->modelClass) {
            throw new InvalidConfigException("Param 'modelClass' mast be definded");
        }
    }

    protected function getController($model)
    {
        if ($this->controller) {
            return $this->controller;
        }

        return Yii::$app->class2controller->getController($this->modelClass);
    }
}