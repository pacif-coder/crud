<?php
namespace app\modules\crud\controls;

use Yii;
use yii\bootstrap\Html;

/**
 * @XXX
 *
 */
class Link extends Button {
    public $url;

    public function html() {
        return Html::a($this->getContent(), $this->url, $this->getAttrs());
    }
}