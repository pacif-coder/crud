<?php
namespace Crud\widgets\assets;

use yii\web\JqueryAsset;

/**
 *
 */
class RefreshAjaxForm extends \Crud\assets\BaseAsset
{
    public $js = [
        'js/refresh-ajax-form.js',
    ];

    public $depends = [
        JqueryAsset::class,
    ];
}