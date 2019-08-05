<?php
namespace app\modules\crud\grid\toolbar;

use yii\web\AssetBundle;

/**
 *
 */
class GridToolbarButtonAsset extends AssetBundle {
    public $sourcePath = __DIR__ . '/asset/';

    public $js = [
        'js/grid-button.js',
    ];

    public $depends = [
        'yii\web\JqueryAsset',
    ];
}
