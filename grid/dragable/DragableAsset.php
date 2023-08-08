<?php
namespace Crud\grid\dragable;

use yii\jui\JuiAsset;

/**
 *
 */
class DragableAsset extends \Crud\assets\BaseAsset
{
    public $js = [
        'js/dragable.js',
    ];

    public $depends = [
        JuiAsset::class,
    ];
}