<?php
namespace app\modules\crud\grid\toolbar;

use yii\jui\JuiAsset;

/**
 *
 */
class GridToolbarButtonAsset extends \yii\web\AssetBundle {
    public $sourcePath = __DIR__ . '/asset/';

    public $js = [
        'js/grid-button.js',
    ];

    public $depends = [
        JuiAsset::class,
    ];
}
