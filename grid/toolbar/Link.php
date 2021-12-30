<?php
namespace app\modules\crud\grid\toolbar;

use yii\helpers\Html;
use yii\helpers\Url;

use app\modules\crud\grid\toolbar\Button;

/**
 * @XXX
 *
 */
class Link extends Button {
    public $url = null;

    public function html() {
        return Html::a($this->getContent(), $this->url, $this->getAttrs());
    }
}
