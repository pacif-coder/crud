<?php
namespace app\modules\crud\controls;

use Yii;
use yii\bootstrap\Html;

/**
 * @XXX
 *
 */
class Cancel extends Link
{
    public $label = 'cancel';

    public $place = 'bottom/right';

    protected static $isUseDefMessageCategory = true;

    public function getUrl()
    {
        $view = Yii::$app->getView();
        return $view->context->getBackUrl();
    }
}