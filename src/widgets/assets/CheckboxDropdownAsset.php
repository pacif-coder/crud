<?php
namespace Crud\widgets\assets;

use yii\web\JqueryAsset;

/**
 *
 */
class CheckboxDropdownAsset extends \Crud\assets\BaseAsset
{
    public $css = [
        'css/checkbox-dropdown.css',
    ];

    public $js = [
        'js/checkbox-dropdown.js',
    ];

    public $depends = [
        JqueryAsset::class,
    ];
}