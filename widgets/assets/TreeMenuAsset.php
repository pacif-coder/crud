<?php
namespace Crud\widgets\assets;

use yii\web\JqueryAsset;

/**
 *
 */
class TreeMenuAsset extends \Crud\assets\BaseAsset
{
    public $css = [
        'css/tree-menu.css',
    ];

    public $js = [
        'js/tree-menu.js',
    ];

    public $depends = [
        JqueryAsset::class,
    ];
}