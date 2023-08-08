<?php
namespace Crud\widgets\assets;

use yii\bootstrap\BootstrapAsset;
use yii\web\JqueryAsset;

/**
 *
 */
class FileInputAsset extends \Crud\assets\BaseAsset
{
    public $css = [
        'css/file-input.css',
    ];

    public $js = [
        'js/file-input.js',
    ];

    public $depends = [
        BootstrapAsset::class,
        JqueryAsset::class,
    ];
}