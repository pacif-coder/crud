<?php
namespace Crud\widgets\assets;

/**
 *
 */
class CheckboxMartixAsset extends \Crud\assets\BaseAsset
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