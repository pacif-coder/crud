<?php
namespace Crud\controls;

use Yii;

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
        if ($this->url) {
            return $this->url;
        }

        $view = Yii::$app->getView();
        return $view->context->getBackUrl();
    }
}