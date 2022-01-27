<?php
namespace app\modules\crud\widgets\assets;

use yii\bootstrap\BootstrapAsset;
use yii\web\JqueryAsset;

/**
 *
 */
class FileInputAsset extends \yii\web\AssetBundle {
    public $sourcePath = __DIR__ . '/file-input';

    public $css = [
        'file-input.css',
    ];

    public $js = [
        'file-input.js',
    ];

    public $depends = [
        BootstrapAsset::class,
        JqueryAsset::class,
    ];
}