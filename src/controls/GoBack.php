<?php
namespace Crud\controls;

use Yii;

/**
 * @XXX
 *
 */
class GoBack extends Link
{
    public $name = 'goback';

    public $icon = 'arrow-left';

    public function getUrl()
    {
        if ($this->url) {
            return $this->url;
        }

        $view = Yii::$app->getView();
        return $view->context->getBackUrl();
    }
}