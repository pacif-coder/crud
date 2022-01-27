<?php
namespace app\modules\crud\widgets\assets;

use yii\web\JqueryAsset;

/**
 *
 */
class RefreshAjaxForm extends \app\modules\crud\assets\BaseAsset
{
    public $js = [
        'js/refresh-ajax-form.js',
    ];

    public $depends = [
        JqueryAsset::class,
    ];
}