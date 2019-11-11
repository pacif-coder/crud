<?php
namespace app\modules\crud\controls;

use yii\bootstrap\Html;
use app\modules\crud\behaviors\BackUrlBehavior;
use app\modules\crud\controls\CopyMessageCategoryInterface;

/**
 * @XXX
 *
 */
class Create extends Button implements CopyMessageCategoryInterface {
    public $label = 'Create item';

    public $place = 'title';

    public $colorClass = 'btn-success';

    public $icon = 'plus';

    public function html() {
        $url = BackUrlBehavior::addBackUrl(['create']);
        return Html::a($this->getContent(), $url, $this->getAttrs());
    }
}