<?php
namespace Crud\grid\toolbar;

use yii\helpers\Html;

/**
 * @XXX
 *
 */
class Link extends Button
{
    public $url;

    public function html()
    {
        return Html::a($this->getContent(), $this->getUrl(), $this->getAttrs());
    }
}