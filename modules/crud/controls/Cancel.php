<?php
namespace app\modules\crud\controls;

use Yii;
use yii\bootstrap\Html;
use app\modules\crud\helpers\ClassI18N;
use yii\web\Request;
use yii\helpers\ArrayHelper;

/**
 * @XXX
 *
 */
class Cancel extends Button {
    public $label = 'cancel';

    public $place = 'bottom';

    public function html() {
        $view = Yii::$app->getView();
        return Html::a($this->getContent(), $view->context->getBackUrl(), $this->getAttrs());
    }
}