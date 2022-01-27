<?php
namespace app\modules\crud\grid\toolbar;

use yii\jui\JuiAsset;

/**
 *
 */
class GridToolbarButtonAsset extends \app\modules\crud\assets\BaseAsset
{
    public $js = [
        'js/grid-button.js',
    ];

    public $depends = [
        JuiAsset::class,
    ];
}
