<?php
namespace app\modules\crud\assets;

use yii\web\AssetBundle;

/**
 *
 */
class CrudAsset extends AssetBundle {
    public $sourcePath = __DIR__ . '/asset/css';

    public $css = [
        'all.css',
    ];

    public $depends = [
        'yii\bootstrap\BootstrapAsset',
    ];
}