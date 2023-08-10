<?php
namespace Crud\widgets\assets;

use yii\bootstrap\BootstrapAsset;
use yii\web\JqueryAsset;

/**
 *
 */
class ProgressBarAsset extends \Crud\assets\BaseAsset
{
    public $css = [
        'css/progress-bar.css',
    ];

    public $depends = [
        BootstrapAsset::class,
        JqueryAsset::class,
    ];
}