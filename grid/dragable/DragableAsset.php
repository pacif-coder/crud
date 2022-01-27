<?php
namespace app\modules\crud\grid\dragable;

use yii\jui\JuiAsset;

/**
 *
 */
class DragableAsset extends \app\modules\crud\assets\BaseAsset
{
    public $js = [
        'js/dragable.js',
    ];

    public $depends = [
        JuiAsset::class,
    ];
}