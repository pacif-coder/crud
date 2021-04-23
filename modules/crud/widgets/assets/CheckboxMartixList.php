<?php
namespace app\modules\crud\widgets\assets;

/**
 *
 */
class CheckboxMartixList extends \yii\web\AssetBundle {
    public $sourcePath = __DIR__ . '/checkbox-martix-list';

    public $css = [
        'css/style.css',
    ];

    public $js = [
        'js/script.js',
    ];

    public $depends = [
        'yii\bootstrap\BootstrapAsset',
        'yii\web\JqueryAsset',
    ];
}