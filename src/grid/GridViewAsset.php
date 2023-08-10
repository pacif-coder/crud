<?php
namespace Crud\grid;

use yii\web\YiiAsset;

/**
 *
 */
class GridViewAsset extends \Crud\assets\BaseAsset
{
    public $css = [
        'css/grid.css',
    ];

    public $depends = [
        YiiAsset::class,
    ];
}
