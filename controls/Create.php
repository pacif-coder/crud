<?php
namespace app\modules\crud\controls;

use yii\bootstrap\Html;

use Yii;
use app\modules\crud\behaviors\BackUrlBehavior;
use app\modules\crud\controls\CopyMessageCategoryInterface;

/**
 * @XXX
 *
 */
class Create extends Button implements CopyMessageCategoryInterface
{
    public $label = 'Create item';
    public $place = 'title';

    public $colorClass = 'btn-success';
    public $icon = 'plus';

    public function html()
    {
        // put on first place action 'create' name
        $get = Yii::$app->request->get();
        array_splice($get, 0, 0, 'create');

        $url = BackUrlBehavior::addBackUrl($get);
        return Html::a($this->getContent(), $url, $this->getAttrs());
    }
}