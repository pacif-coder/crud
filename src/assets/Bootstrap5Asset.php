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
    public $sourcePath = '@bower/bootstrap/dist';

    /**
     * @inheritDoc
     */
    public $css = [
        'css/bootstrap.min.css'
    ];

    /**
     * @inheritDoc
     */
    public $js = [
        'js/bootstrap.bundle.min.js'
    ];
}