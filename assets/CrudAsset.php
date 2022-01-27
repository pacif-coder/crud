<?php
namespace app\modules\crud\assets;

use yii\bootstrap\BootstrapAsset;
use yii\web\JqueryAsset;

/**
 *
 */
class CrudAsset extends BaseAsset
{
    public $css = [
        'css/all.css',
    ];

    public $js = [
        'js/submit-form-button.js',
    ];

    public $depends = [
        BootstrapAsset::class,
        JqueryAsset::class,
    ];
}