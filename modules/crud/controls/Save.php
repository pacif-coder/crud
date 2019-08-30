<?php
namespace app\modules\crud\controls;

use Yii;
use yii\bootstrap\Html;
use app\modules\crud\helpers\ClassI18N;
use yii\web\Request;
use yii\helpers\ArrayHelper;

/**
 * @XXX
 *
 */
class Save extends Button {
    public $label = 'Сохранить';

    public $icon = 'ok';

    public $colorClass = 'btn-success';

    public $place = 'bottom';

    public $options = [
        'type' => 'submit',
    ];
}