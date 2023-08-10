<?php
namespace Crud\controls;

use yii\helpers\Html;

/**
 * Link as button
 *
 */
class Link extends Base
{
    public $url;

    public function html()
    {
        return Html::a($this->getContent(), $this->getUrl(), $this->getAttrs());
    }

    public function getUrl()
    {
        return $this->url? : parent::getUrl();
    }
}
