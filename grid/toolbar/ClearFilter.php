<?php
namespace app\modules\crud\grid\toolbar;

use yii\helpers\Html;
use yii\helpers\Url;
use yii\grid\GridView;

use app\modules\crud\grid\toolbar\Button;

/**
 * @XXX
 *
 */
class ClearFilter extends Button {
    public $icon = 'remove';

    public $label = 'Clear filter';

    public $order = -1;

    public function html() {
        if (!$this->grid || !($this->grid instanceof GridView)
                || !$this->grid->filterModel
                || !$this->grid->filterModel->isLoaded()) {

            return '';
        }

        // drop all form filter params
        $url = Url::current([$this->grid->filterModel->formName() => null]);
        return Html::a($this->getContent(), $url, $this->getAttrs());
    }
}
