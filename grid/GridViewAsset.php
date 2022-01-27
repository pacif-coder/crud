<?php
namespace app\modules\crud\grid;

use yii\web\YiiAsset;
use yii\bootstrap\BootstrapAsset;

/**
 *
 */
class GridViewAsset extends \app\modules\crud\assets\BaseAsset
{
    public $css = [
        'css/grid.css',
    ];

    public $depends = [
        YiiAsset::class,
        BootstrapAsset::class,
    ];
}
