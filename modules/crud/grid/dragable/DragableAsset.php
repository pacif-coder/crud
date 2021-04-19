<?php
namespace app\modules\crud\grid\dragable;

use yii\jui\JuiAsset;

/**
 *
 */
class DragableAsset extends \yii\web\AssetBundle {
    public $sourcePath = __DIR__ . '/asset/';

    public $js = [
        'dragable.js',
    ];

    public $depends = [
        JuiAsset::class,
    ];
}