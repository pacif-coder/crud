<?php
namespace app\modules\crud\grid\toolbar;

use Yii;
use yii\helpers\Html;
use yii\helpers\Url;
use app\modules\crud\grid\toolbar\Button;

/**
 * @XXX
 *
 */
class ClearFilter extends Button {
    public $icon = 'remove';

    public $label = 'Clear filter';

    public $align = 'right';

    public $order = -1;

    public function html() {
        if (!$this->grid || !$this->grid->filterModel || !$this->grid->filterModel->isLoaded()) {
            return '';
        }

        return Html::a($this->getContent(), $this->grid->getClearFilterUrl(), $this->getAttrs());
    }
}
