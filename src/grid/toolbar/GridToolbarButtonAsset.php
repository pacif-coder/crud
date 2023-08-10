<?php
namespace Crud\grid\toolbar;

use yii\jui\JuiAsset;

/**
 *
 */
class GridToolbarButtonAsset extends \Crud\assets\BaseAsset
{
    public $js = [
        'js/grid-button.js',
    ];

    public $depends = [
//        JuiAsset::class,
    ];
}
