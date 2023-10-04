<?php
namespace Crud\widgets\assets;

use yii\jui\JuiAsset;

/**
 *
 */
class JuiWidgetAsset extends \Crud\assets\BaseAsset
{
    public $js = [
        'js/jui-widget.js',
    ];

    public $depends = [
        JuiAsset::class,
    ];
}