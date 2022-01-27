<?php
namespace app\modules\crud\widgets\assets;

use yii\bootstrap\BootstrapAsset;
use yii\web\JqueryAsset;

/**
 *
 */
class ProgressBarAsset extends \app\modules\crud\assets\BaseAsset
{
    public $css = [
        'css/progress-bar.css',
    ];

    public $depends = [
        BootstrapAsset::class,
        JqueryAsset::class,
    ];
}