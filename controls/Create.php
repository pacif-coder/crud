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

    public $action = 'create';

    public function getUrl()
    {
        return BackUrlBehavior::addBackUrl(parent::getUrl());
    }
}