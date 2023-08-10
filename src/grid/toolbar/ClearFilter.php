<?php
namespace Crud\grid\toolbar;

use yii\helpers\Url;
use yii\grid\GridView;

/**
 * @XXX
 *
 */
class ClearFilter extends Link
{
    public $icon = 'remove';

    public $label = 'Clear filter';

    public $order = -1;

    protected static $isUseDefMessageCategory = true;

    public function getUrl()
    {
        // drop all form filter params
        return Url::current([$this->grid->filterModel->formName() => null]);
    }

    public function isShow()
    {
        if (!$this->grid || !($this->grid instanceof GridView)) {
            return false;
        }

        if (!$this->grid->filterModel || !$this->grid->filterModel->isLoaded()) {
            return false;
        }

        return parent::isShow();
    }
}
