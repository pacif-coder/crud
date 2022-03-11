<?php
namespace app\modules\crud\widgets\assets;

/**
 *
 */
class CheckboxMartixAsset extends \app\modules\crud\assets\BaseAsset
{
    public $css = [
        'css/checkbox-martix-list.css',
    ];

    public $js = [
        'js/checkbox-martix-list.js',
    ];

    public $depends = [
        'yii\bootstrap\BootstrapAsset',
        'yii\web\JqueryAsset',
    ];
}