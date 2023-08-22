<?php
namespace Crud\assets;



/**
 *
 */
class Bootstrap5Asset extends \yii\web\AssetBundle
{
    /**
     * @inheritDoc
     */
    public $sourcePath = '@bower/bootstrap';

    /**
     * @inheritDoc
     */
    public $css = [
        'dist/css/bootstrap.min.css'
    ];

    /**
     * @inheritDoc
     */
    public $js = [
        'dist/js/bootstrap.bundle.min.js'
    ];
}