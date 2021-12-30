<?php
namespace app\modules\crud\grid;

use yii\web\AssetBundle;

/**
 *
 */
class GridViewAsset extends AssetBundle {
    public $sourcePath = __DIR__ . '/asset';

    public $css = [
        'css/grid.css',
    ];

    public $depends = [
        'yii\web\YiiAsset',
        'yii\bootstrap\BootstrapAsset',
    ];
}
