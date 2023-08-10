<?php
namespace Crud\assets;

use Yii;
use yii\bootstrap\BootstrapAsset as Bootstrap3Asset;
use yii\bootstrap5\BootstrapIconAsset as Bootstrap5IconAsset;
use yii\web\JqueryAsset;

use Crud\assets\Bootstrap5Asset;
use Crud\helpers\Html;

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
        JqueryAsset::class,
    ];

    public function init()
    {
        parent::init();

        if (5 == Html::getBootstrapVersion()) {
            $this->depends[] = Bootstrap5Asset::class;
            $this->depends[] = Bootstrap5IconAsset::class;
        } else {
            $this->depends[] = Bootstrap3Asset::class;
        }
    }

    public function registerAssetFiles($view)
    {
        $view->registerMetaTag(['charset' => Yii::$app->charset], 'charset');

        $view->registerCsrfMetaTags();
        $view->metaTags['csrf_meta_tags'] = trim($view->metaTags['csrf_meta_tags']);

        parent::registerAssetFiles($view);
    }
}