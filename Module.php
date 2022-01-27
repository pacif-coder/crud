<?php
namespace app\modules\crud;

use app\modules\crud\helpers\Lang;

/**
 * CRUD module definition class
 */
class Module extends \yii\base\Module
{
    public $defaultRoute = 'crud';

    /**
     *
     */
    public function init() {
        parent::init();

        $this->registerTranslations();
    }

    public function registerTranslations()
    {
        Lang::addCategory2Path(__NAMESPACE__, __DIR__ . '/messages');
    }
}
