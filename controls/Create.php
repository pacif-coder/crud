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
class Create extends Link implements CopyMessageCategoryInterface
{
    public $label = 'Create item';
    public $place = 'title';

    public $colorClass = 'btn-success';
    public $icon = 'plus';

    public function getUrl()
    {
        // put on first place action 'create' name
        $get = Yii::$app->request->get();
        $get[0] = 'create';

        return BackUrlBehavior::addBackUrl($get);
    }
}