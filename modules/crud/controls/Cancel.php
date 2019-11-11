<?php
namespace app\modules\crud\controls;

use Yii;
use yii\bootstrap\Html;

/**
 * @XXX
 *
 */
class Cancel extends Button {
    public $label = 'cancel';

    public $place = 'bottom/right';

    public function html() {
        $view = Yii::$app->getView();
        return Html::a($this->getContent(), $view->context->getBackUrl(), $this->getAttrs());
    }
}